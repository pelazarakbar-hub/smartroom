<?php

namespace App\Services;

use App\Models\InsightFeedback;
use App\Models\Room;
use App\Models\User;

/**
 * Rekomendasi berbasis aturan dari data sensor 2 jam terakhir.
 * (Belum memakai model AI sungguhan; tampilannya mengikuti desain "AI Insight".)
 */
class InsightService
{
    public const STALE_MINUTES = 10;

    public function forRoom(Room $room, User $user): array
    {
        $room->loadMissing('devices');

        $latest = $room->latestReading();
        $recent = $room->readings()->where('recorded_at', '>=', now()->subHours(2))->get();
        $feedback = InsightFeedback::where('user_id', $user->id)
            ->where('room_id', $room->id)
            ->get()
            ->keyBy('insight_key');

        $items = [
            $this->fanInsight($room, $latest, $recent),
            $this->lampInsight($room, $latest),
            $this->sensorInsight($latest),
        ];

        foreach ($items as &$item) {
            $item['helpful'] = $feedback->get($item['key'])?->helpful;
        }
        unset($item);

        return $items;
    }

    private function fanInsight(Room $room, $latest, $recent): array
    {
        $average = $recent->count() > 0 ? round($recent->avg('temperature'), 1) : null;
        $hot = $average !== null && $recent->count() >= 3 && $average > 30;
        $applied = $room->auto_fan_above !== null;
        $occupied = $latest && $latest->occupants > 0;

        if ($average === null) {
            $description = 'Belum ada data suhu dalam 2 jam terakhir.';
        } elseif ($hot) {
            $description = "Suhu rata-rata 2 jam terakhir {$average}°C, di atas batas nyaman 30°C.";
        } else {
            $description = "Suhu rata-rata 2 jam terakhir {$average}°C, masih dalam rentang aman.";
        }

        return [
            'key' => 'fan',
            'title' => $hot ? 'Gunakan kipas saat suhu tinggi' : 'Suhu ruangan terkendali',
            'description' => $description,
            'reason' => $hot
                ? 'Suhu tinggi berlangsung cukup lama'.($occupied ? ' dan ruangan sedang terisi.' : '.')
                : null,
            'actionable' => $hot || $applied,
            'applied' => $applied,
            'applied_text' => $applied
                ? 'Kipas akan menyala saat suhu melebihi '.rtrim(rtrim(number_format($room->auto_fan_above, 1), '0'), '.').'°C.'
                : null,
            'chip' => null,
        ];
    }

    private function lampInsight(Room $room, $latest): array
    {
        $lampOn = $room->devices->where('type', 'lamp')->contains('is_on', true);

        if (! $latest) {
            $title = 'Pencahayaan belum terpantau';
            $description = 'Belum ada data cahaya dari sensor.';
        } elseif ($latest->light > 500 && $lampOn) {
            $title = 'Kurangi penggunaan lampu';
            $description = "Cahaya ruangan {$latest->light} lx, sudah cukup terang.";
        } elseif ($latest->light < 250) {
            $title = 'Tambah pencahayaan';
            $description = "Cahaya ruangan hanya {$latest->light} lx.";
        } else {
            $title = 'Pencahayaan sudah sesuai';
            $description = "Cahaya ruangan {$latest->light} lx berada di rentang nyaman.";
        }

        return [
            'key' => 'lamp',
            'title' => $title,
            'description' => $description,
            'reason' => null,
            'actionable' => false,
            'applied' => false,
            'applied_text' => null,
            'chip' => null,
        ];
    }

    private function sensorInsight($latest): array
    {
        $stale = ! $latest || $latest->recorded_at->lt(now()->subMinutes(self::STALE_MINUTES));

        if ($stale) {
            return [
                'key' => 'sensor',
                'title' => 'Periksa sensor ruangan',
                'description' => $latest
                    ? 'Tidak mengirim data sejak '.$latest->recorded_at->diffForHumans(null, true).' lalu.'
                    : 'Sensor belum pernah mengirim data.',
                'reason' => null,
                'actionable' => false,
                'applied' => false,
                'applied_text' => null,
                'chip' => ['tone' => 'error', 'icon' => 'warning', 'label' => 'Offline'],
            ];
        }

        return [
            'key' => 'sensor',
            'title' => 'Semua sensor aktif',
            'description' => 'Data terakhir diterima '.$latest->recorded_at->diffForHumans().'.',
            'reason' => null,
            'actionable' => false,
            'applied' => false,
            'applied_text' => null,
            'chip' => ['tone' => 'success', 'icon' => 'check', 'label' => 'Online'],
        ];
    }
}
