<?php

namespace App\Services;

use App\Models\Device;
use App\Models\InsightFeedback;
use App\Models\Room;
use App\Models\SensorReading;
use App\Models\User;
use App\Support\Comfort;
use App\Support\Status;
use Illuminate\Support\Collection;

/**
 * Analisis ruangan berbasis aturan dari data sensor 2 jam terakhir:
 * skor kenyamanan, tren suhu, dan rekomendasi yang diurutkan menurut prioritas.
 * (Belum memakai model AI sungguhan; tampilannya mengikuti desain "AI Insight".)
 */
class InsightService
{
    public const STALE_MINUTES = 10;

    /** Kunci rekomendasi yang dikenal (dipakai untuk umpan balik dan penerapan saran). */
    public const KEYS = ['fan', 'energy', 'humidity', 'air', 'lamp', 'sensor'];

    private const SEVERITY_ORDER = ['high' => 0, 'medium' => 1, 'low' => 2, 'ok' => 3];

    /**
     * @return array{score: int|null, label: string, tone: string, headline: string, samples: int, insights: array<int, array<string, mixed>>}
     */
    public function analyze(Room $room, User $user): array
    {
        $room->loadMissing('devices');

        $latest = $room->latestReading();
        $recent = $room->readings()
            ->where('recorded_at', '>=', now()->subHours(2))
            ->orderBy('recorded_at')
            ->get();

        $stale = ! $latest || $latest->recorded_at->lt(now()->subMinutes(self::STALE_MINUTES));

        if ($stale) {
            // Data lama tidak dipakai untuk memberi saran: yang disarankan hanya memeriksa sensor.
            $summary = [
                'score' => null,
                'label' => 'Data belum tersedia',
                'tone' => 'warning',
                'headline' => $latest
                    ? 'Data sensor sudah tidak terbaru, jadi saran lain ditahan sampai sensor mengirim data lagi.'
                    : 'Belum ada data sensor. Sambungkan perangkat atau jalankan simulator untuk melihat analisis.',
                'samples' => $recent->count(),
            ];
            $items = [$this->sensorInsight($latest, true)];
        } else {
            $direction = $this->temperatureDirection($recent);
            $summary = $this->summary($latest, $recent, $direction);
            $items = array_values(array_filter([
                $this->fanInsight($room, $latest, $recent, $direction),
                $this->energyInsight($room, $latest),
                $this->humidityInsight($latest),
                $this->airInsight($latest),
                $this->lampInsight($room, $latest),
                $this->sensorInsight($latest, false),
            ]));
        }

        $items = $this->sortByPriority($items);

        $feedback = InsightFeedback::where('user_id', $user->id)
            ->where('room_id', $room->id)
            ->get()
            ->keyBy('insight_key');

        foreach ($items as &$item) {
            $item['helpful'] = $feedback->get($item['key'])?->helpful;
        }
        unset($item);

        return $summary + ['insights' => $items];
    }

    /** Hanya daftar rekomendasi (kompatibel dengan pemakaian lama). */
    public function forRoom(Room $room, User $user): array
    {
        return $this->analyze($room, $user)['insights'];
    }

    /**
     * Perangkat yang sebaiknya dimatikan karena ruangan kosong: lampu menyala, atau kipas menyala
     * padahal suhu tidak tinggi. Hanya perangkat yang online. Kosong bila data sensor tidak terbaru
     * atau ruangan sedang terisi.
     *
     * @return Collection<int, Device>
     */
    public function energyTargets(Room $room): Collection
    {
        $latest = $room->latestReading();

        if (! $latest || $latest->recorded_at->lt(now()->subMinutes(self::STALE_MINUTES)) || $latest->occupants === null || $latest->occupants > 0) {
            return collect();
        }

        return $room->devices()->get()->filter(fn (Device $device) => $device->is_on
            && $device->isOnline()
            && ($device->type === 'lamp' || ($device->type === 'fan' && $latest->temperature <= 28)));
    }

    // ------------------------------------------------------------ ringkasan

    private function summary(SensorReading $latest, Collection $recent, ?string $direction): array
    {
        $score = Comfort::score((float) $latest->temperature, $latest->humidity, $latest->air_quality, $latest->light);
        $label = Comfort::label($score);

        $opening = match ($label['tone']) {
            'success' => 'Ruangan terasa nyaman.',
            'warning' => 'Ruangan cukup nyaman, tetapi ada hal yang perlu diperhatikan.',
            default => 'Ruangan kurang nyaman saat ini.',
        };

        $temperature = 'Suhu '.$latest->temperature.'°C'.match ($direction) {
            'naik' => ' dan sedang naik',
            'turun' => ' dan sedang turun',
            'stabil' => ' dan stabil',
            default => '',
        };

        // Hanya sebut sensor yang benar-benar mengirim data.
        $parts = [$temperature];
        if ($latest->humidity !== null) {
            $parts[] = "kelembapan {$latest->humidity}%";
        }
        if ($latest->air_quality !== null) {
            $parts[] = 'kualitas udara '.mb_strtolower(Status::air($latest->air_quality)['label']);
        }

        return [
            'score' => $score,
            'label' => $label['label'],
            'tone' => $label['tone'],
            'headline' => $opening.' '.implode(', ', $parts).'.',
            'samples' => $recent->count(),
        ];
    }

    /** Bandingkan suhu rata-rata 15 menit terakhir dengan 15-45 menit sebelumnya. */
    private function temperatureDirection(Collection $recent): ?string
    {
        $newer = $recent->where('recorded_at', '>=', now()->subMinutes(15))->pluck('temperature')->all();
        $older = $recent
            ->where('recorded_at', '<', now()->subMinutes(15))
            ->where('recorded_at', '>=', now()->subMinutes(45))
            ->pluck('temperature')
            ->all();

        return Comfort::direction(Comfort::change($older, $newer));
    }

    // ------------------------------------------------------------ rekomendasi

    private function fanInsight(Room $room, SensorReading $latest, Collection $recent, ?string $direction): array
    {
        $average = round($recent->avg('temperature'), 1);
        $hot = $recent->count() >= 3 && $average > 30;
        $rising = ! $hot && $direction === 'naik' && $latest->temperature > 27.5;
        $applied = $room->auto_fan_above !== null;
        $occupied = $latest->occupants > 0;
        $limit = $applied ? rtrim(rtrim(number_format($room->auto_fan_above, 1), '0'), '.') : null;

        if ($hot) {
            $title = 'Gunakan kipas saat suhu tinggi';
            $description = "Suhu rata-rata 2 jam terakhir {$average}°C, di atas batas nyaman 30°C.";
            $reason = 'Suhu tinggi berlangsung cukup lama'
                .($direction === 'naik' ? ', masih terus naik' : '')
                .($occupied ? ' dan ruangan sedang terisi.' : '.');
            $severity = $applied ? 'low' : 'high';
        } elseif ($rising) {
            $title = 'Suhu mulai naik';
            $description = "Suhu sekarang {$latest->temperature}°C dan lebih tinggi dibanding 30 menit lalu.";
            $reason = 'Jika dibiarkan, suhu bisa melewati batas nyaman 30°C. Kipas otomatis membantu menahannya.';
            $severity = $applied ? 'low' : 'medium';
        } else {
            $title = 'Suhu ruangan terkendali';
            $description = "Suhu rata-rata 2 jam terakhir {$average}°C, masih dalam rentang aman.";
            $reason = null;
            $severity = 'ok';
        }

        return $this->item([
            'key' => 'fan',
            'title' => $title,
            'description' => $description,
            'reason' => $reason,
            'severity' => $severity,
            'actionable' => $hot || $rising || $applied,
            'applied' => $applied,
            'action_label' => 'Terapkan Saran',
            'done_label' => 'Saran diterapkan',
            'applied_text' => $applied ? "Kipas akan menyala saat suhu melebihi {$limit}°C." : null,
        ]);
    }

    private function energyInsight(Room $room, SensorReading $latest): ?array
    {
        $targets = $this->energyTargets($room);

        if ($targets->isEmpty()) {
            return null;
        }

        $names = $targets->pluck('name')->join(', ', ' dan ');

        return $this->item([
            'key' => 'energy',
            'title' => 'Matikan perangkat saat ruangan kosong',
            'description' => "Tidak ada orang di ruangan, tetapi {$names} masih menyala.",
            'reason' => 'Sensor keberadaan tidak mendeteksi siapa pun. Mematikan perangkat yang tidak dipakai menghemat listrik.',
            'severity' => 'medium',
            'actionable' => true,
            'action_label' => 'Matikan sekarang',
            'done_label' => 'Perangkat dimatikan',
        ]);
    }

    private function humidityInsight(SensorReading $latest): ?array
    {
        $humidity = $latest->humidity;

        if ($humidity === null || ($humidity >= 40 && $humidity <= 70)) {
            return null;
        }

        $dry = $humidity < 40;

        return $this->item([
            'key' => 'humidity',
            'title' => $dry ? 'Udara cenderung kering' : 'Kelembapan tinggi',
            'description' => "Kelembapan {$humidity}%, ".($dry ? 'di bawah' : 'di atas').' rentang nyaman 40–70%.',
            'reason' => $dry
                ? 'Udara kering bisa membuat mata dan tenggorokan tidak nyaman. Pertimbangkan pelembap udara atau menaruh wadah air.'
                : 'Kelembapan tinggi membuat ruangan terasa pengap. Buka ventilasi atau gunakan kipas agar udara bergerak.',
            'severity' => 'medium',
        ]);
    }

    private function airInsight(SensorReading $latest): ?array
    {
        $aqi = $latest->air_quality;

        if ($aqi === null || $aqi <= 50) {
            return null;
        }

        $bad = $aqi > 100;

        return $this->item([
            'key' => 'air',
            'title' => $bad ? 'Kualitas udara buruk' : 'Kualitas udara sedang',
            'description' => "Indeks kualitas udara {$aqi}, ".($bad ? 'di atas 100.' : 'di atas batas baik (50).'),
            'reason' => 'Buka ventilasi atau jendela untuk menukar udara, dan cari sumber asap atau debu di dalam ruangan.',
            'severity' => $bad ? 'high' : 'medium',
        ]);
    }

    private function lampInsight(Room $room, SensorReading $latest): ?array
    {
        if ($latest->light === null) {
            return null; // tanpa sensor cahaya, kondisi pencahayaan tidak bisa dinilai
        }

        $lampOn = $room->devices->where('type', 'lamp')->contains('is_on', true);

        if ($latest->light > 500 && $lampOn) {
            $title = 'Kurangi penggunaan lampu';
            $description = "Cahaya ruangan {$latest->light} lx, sudah cukup terang.";
            $severity = 'low';
        } elseif ($latest->light < 250) {
            $title = 'Tambah pencahayaan';
            $description = "Cahaya ruangan hanya {$latest->light} lx.";
            $severity = 'low';
        } else {
            $title = 'Pencahayaan sudah sesuai';
            $description = "Cahaya ruangan {$latest->light} lx berada di rentang nyaman.";
            $severity = 'ok';
        }

        return $this->item(['key' => 'lamp', 'title' => $title, 'description' => $description, 'severity' => $severity]);
    }

    private function sensorInsight(?SensorReading $latest, bool $stale): array
    {
        if ($stale) {
            return $this->item([
                'key' => 'sensor',
                'title' => 'Periksa sensor ruangan',
                'description' => $latest
                    ? 'Tidak mengirim data sejak '.$latest->recorded_at->diffForHumans(null, true).' lalu.'
                    : 'Sensor belum pernah mengirim data.',
                'severity' => 'high',
                'chip' => ['tone' => 'error', 'icon' => 'warning', 'label' => 'Offline'],
            ]);
        }

        return $this->item([
            'key' => 'sensor',
            'title' => 'Semua sensor aktif',
            'description' => 'Data terakhir diterima '.$latest->recorded_at->diffForHumans().'.',
            'severity' => 'ok',
            'chip' => ['tone' => 'success', 'icon' => 'check', 'label' => 'Online'],
        ]);
    }

    // ------------------------------------------------------------ pembantu

    /** Lengkapi bidang yang tidak diisi dan tentukan label (chip) dari tingkat prioritas. */
    private function item(array $fields): array
    {
        $item = $fields + [
            'reason' => null,
            'severity' => 'ok',
            'actionable' => false,
            'applied' => false,
            'applied_text' => null,
            'action_label' => 'Terapkan Saran',
            'done_label' => 'Saran diterapkan',
            'chip' => null,
        ];

        $item['chip'] ??= match ($item['severity']) {
            'high' => ['tone' => 'error', 'icon' => 'warning', 'label' => 'Penting'],
            'medium' => ['tone' => 'warning', 'icon' => 'warning', 'label' => 'Perlu perhatian'],
            'low' => ['tone' => 'info', 'icon' => 'info', 'label' => 'Saran'],
            default => ['tone' => 'success', 'icon' => 'check', 'label' => 'Baik'],
        };

        return $item;
    }

    /** Urutkan: penting dulu. Urutan awal dipertahankan untuk prioritas yang sama. */
    private function sortByPriority(array $items): array
    {
        $indexed = [];
        foreach ($items as $position => $item) {
            $indexed[] = [self::SEVERITY_ORDER[$item['severity']] ?? 9, $position, $item];
        }

        usort($indexed, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_map(fn ($row) => $row[2], $indexed);
    }
}
