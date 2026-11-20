<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\InsightFeedback;
use App\Models\SensorReading;
use App\Services\InsightService;
use App\Support\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman untuk pengguna biasa: Beranda, Ruangan, Perangkat, AI Insight, Profil.
 */
class AppController extends Controller
{
    private const FRESH_MINUTES = 10;

    // ---------------------------------------------------------------- halaman

    public function home(Request $request): View
    {
        $ctx = $this->context($request);

        if (! $ctx['room']) {
            return $this->noRoom($ctx, 'Beranda');
        }

        $ctx['room']->load('devices');
        $fan = $ctx['room']->devices->firstWhere('type', 'fan');

        return view('app.home', $ctx + [
            'fan' => $fan,
            'sensors' => $this->sensors($ctx['reading']),
            'banner' => $this->banner($ctx['reading'], $fan),
        ]);
    }

    public function room(Request $request): View
    {
        $ctx = $this->context($request);

        if (! $ctx['room']) {
            return $this->noRoom($ctx, 'Monitoring Ruangan');
        }

        $room = $ctx['room'];
        $room->load('devices');
        $reading = $ctx['reading'];

        $since = now()->subHours(6);
        $readings = $room->readings()
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at')
            ->get();

        $temperatures = $readings->pluck('temperature')->all();
        $humidities = $readings->pluck('humidity')->all();

        $fanOn = $room->devices->where('type', 'fan')->contains('is_on', true);
        $lampOn = $room->devices->where('type', 'lamp')->contains('is_on', true);

        return view('app.room', $ctx + [
            'tempChart' => Status::chart($temperatures),
            'humChart' => Status::chart($humidities),
            'tempTrend' => Status::trend($temperatures, ($reading && $reading->temperature > 30) ? 'Stabil tinggi' : 'Stabil'),
            'humTrend' => Status::trend($humidities),
            'axis' => [$since->format('H.i'), $since->copy()->addHours(3)->format('H.i'), now()->format('H.i')],
            'details' => $this->details($reading, $fanOn, $lampOn),
        ]);
    }

    public function selectRoom(Request $request): RedirectResponse
    {
        $data = $request->validate(['room_id' => ['required', 'integer']]);

        if ($request->user()->rooms()->whereKey($data['room_id'])->exists()) {
            $request->session()->put('room_id', (int) $data['room_id']);
        }

        return redirect()->route('app.room');
    }

    public function devices(Request $request): View
    {
        $ctx = $this->context($request);

        if (! $ctx['room']) {
            return $this->noRoom($ctx, 'Kontrol Perangkat');
        }

        $devices = $ctx['room']->devices()->get();

        return view('app.devices', $ctx + [
            'devices' => $devices,
            'connected' => $devices->count(),
            'online' => $devices->filter(fn ($device) => $device->isOnline())->count(),
            'active' => $devices->where('is_on', true)->count(),
        ]);
    }

    public function insight(Request $request): View
    {
        $ctx = $this->context($request);

        if (! $ctx['room']) {
            return $this->noRoom($ctx, 'AI Insight');
        }

        return view('app.insight', $ctx + [
            'analysis' => app(InsightService::class)->analyze($ctx['room'], $ctx['user']),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('app.profile', $this->context($request));
    }

    public function activity(Request $request): View
    {
        $ctx = $this->context($request);
        $roomIds = $ctx['rooms']->pluck('id');

        $logs = DeviceLog::with(['device.room', 'user'])
            ->whereHas('device', fn ($query) => $query->whereIn('room_id', $roomIds))
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return view('app.activity', $ctx + ['logs' => $logs]);
    }

    // ---------------------------------------------------------------- aksi

    public function toggleDevice(Request $request, Device $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        if (! $device->isOnline()) {
            return response()->json(['message' => $device->name.' sedang offline.'], 422);
        }

        $on = $request->has('on') ? $request->boolean('on') : ! $device->is_on;
        $device->turn($on, $request->user());

        return response()->json([
            'ok' => true,
            'is_on' => $device->is_on,
            'level' => $device->level,
        ]);
    }

    public function setSpeed(Request $request, Device $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        $data = $request->validate(['level' => ['required', 'integer', 'between:1,3']]);

        if (! $device->isOnline()) {
            return response()->json(['message' => $device->name.' sedang offline.'], 422);
        }

        if ($device->type !== 'fan' || ! $device->is_on) {
            return response()->json(['message' => 'Nyalakan kipas untuk mengatur kecepatan.'], 422);
        }

        $device->setLevel((int) $data['level'], $request->user());

        return response()->json(['ok' => true, 'level' => $device->level]);
    }

    public function applyInsight(Request $request, string $key): JsonResponse
    {
        abort_unless(in_array($key, ['fan', 'energy'], true), 404);

        $ctx = $this->context($request);
        abort_unless($ctx['room'], 404);

        if ($key === 'fan') {
            $ctx['room']->update(['auto_fan_above' => 30]);
            $message = 'Kipas akan menyala saat suhu melebihi 30°C.';
            $notice = 'Aturan otomatis berhasil dibuat dan sudah aktif.';
        } else {
            // Data dicek ulang di server: hanya perangkat yang memang layak dimatikan.
            $targets = app(InsightService::class)->energyTargets($ctx['room']);

            if ($targets->isEmpty()) {
                return response()->json(['message' => 'Tidak ada perangkat yang perlu dimatikan sekarang.'], 422);
            }

            $targets->each(fn (Device $device) => $device->turn(false, $ctx['user']));
            $message = $targets->pluck('name')->join(', ', ' dan ').' dimatikan.';
            $notice = 'Perangkat yang tidak dipakai sudah dimatikan.';
        }

        InsightFeedback::updateOrCreate(
            ['user_id' => $ctx['user']->id, 'room_id' => $ctx['room']->id, 'insight_key' => $key],
            ['applied' => true]
        );

        return response()->json(['ok' => true, 'message' => $message, 'notice' => $notice]);
    }

    public function insightFeedback(Request $request, string $key): JsonResponse
    {
        abort_unless(in_array($key, InsightService::KEYS, true), 404);

        $data = $request->validate(['helpful' => ['required', 'boolean']]);

        $ctx = $this->context($request);
        abort_unless($ctx['room'], 404);

        InsightFeedback::updateOrCreate(
            ['user_id' => $ctx['user']->id, 'room_id' => $ctx['room']->id, 'insight_key' => $key],
            ['helpful' => (bool) $data['helpful']]
        );

        return response()->json(['ok' => true]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.min' => 'Nama harus berisi minimal 2 karakter.',
        ]);

        $request->user()->update(['name' => trim($data['name'])]);

        return redirect()->route('app.profile')->with('status', 'Profil berhasil disimpan.');
    }

    public function updateSetting(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'in:privacy_mode,notifications']]);

        $user = $request->user();
        $key = $data['key'];
        $user->update([$key => ! $user->{$key}]);

        return response()->json(['ok' => true, 'key' => $key, 'value' => (bool) $user->{$key}]);
    }

    // ---------------------------------------------------------------- bantu

    /**
     * Data umum yang dipakai semua halaman pengguna: ruangan terpilih dan data sensor terakhir.
     */
    private function context(Request $request): array
    {
        $user = $request->user();
        $rooms = $user->rooms()->orderBy('name')->get();
        $room = $rooms->firstWhere('id', (int) $request->session()->get('room_id')) ?? $rooms->first();

        $reading = $room?->latestReading();
        $fresh = $reading && $reading->recorded_at->gte(now()->subMinutes(self::FRESH_MINUTES));

        // Titik di ikon lonceng: ada hal yang perlu diperhatikan.
        $alert = $room && (! $fresh || $reading->temperature > 30);

        return [
            'user' => $user,
            'rooms' => $rooms,
            'room' => $room,
            'reading' => $reading,
            'fresh' => (bool) $fresh,
            'alert' => (bool) $alert,
        ];
    }

    private function noRoom(array $ctx, string $title): View
    {
        return view('app.no-room', $ctx + ['title' => $title]);
    }

    private function authorizeDevice(Request $request, Device $device): void
    {
        abort_unless(
            $request->user()->rooms()->whereKey($device->room_id)->exists(),
            403,
            'Perangkat ini bukan milik ruangan Anda.'
        );
    }

    private function sensors(?SensorReading $r): array
    {
        if (! $r) {
            $none = ['value' => '–', 'note' => 'Belum ada data', 'tone' => 'info', 'status_icon' => 'info'];

            return [
                ['label' => 'Suhu', 'icon' => 'thermometer'] + $none,
                ['label' => 'Kelembapan', 'icon' => 'droplet'] + $none,
                ['label' => 'Intensitas Cahaya', 'icon' => 'sun'] + $none,
                ['label' => 'Keberadaan', 'icon' => 'users'] + $none,
            ];
        }

        $temperature = Status::temperature($r->temperature);
        $humidity = Status::humidity($r->humidity);
        $light = Status::light($r->light);

        return [
            [
                'label' => 'Suhu', 'icon' => 'thermometer',
                'value' => number_format($r->temperature, 1).'°C',
                'note' => $temperature['label'], 'tone' => $temperature['tone'], 'status_icon' => $temperature['icon'],
            ],
            [
                'label' => 'Kelembapan', 'icon' => 'droplet',
                'value' => $r->humidity.'%',
                'note' => $humidity['label'], 'tone' => $humidity['tone'], 'status_icon' => $humidity['icon'],
            ],
            [
                'label' => 'Intensitas Cahaya', 'icon' => 'sun',
                'value' => $r->light.' lx',
                'note' => $light['label'], 'tone' => $light['tone'], 'status_icon' => $light['icon'],
            ],
            [
                'label' => 'Keberadaan', 'icon' => 'users',
                'value' => $r->occupants > 0 ? 'Ada' : 'Kosong',
                'note' => $r->occupants > 0 ? $r->occupants.' orang' : 'Tidak ada orang',
                'tone' => 'info', 'status_icon' => 'info',
            ],
        ];
    }

    private function banner(?SensorReading $r, ?Device $fan): array
    {
        if (! $r) {
            return [
                'title' => 'Belum ada data sensor',
                'description' => 'Sensor belum mengirim data. Pastikan perangkat sudah terhubung ke internet.',
            ];
        }

        $t = number_format($r->temperature, 1);

        if ($fan && $fan->is_on) {
            return $r->temperature > 28
                ? ['title' => 'Kipas sedang mendinginkan ruangan', 'description' => "Suhu kini {$t}°C dan sedang dijaga menuju rentang nyaman."]
                : ['title' => 'Suhu ruangan sudah nyaman', 'description' => "Suhu stabil di {$t}°C. Kipas dapat dimatikan bila sudah cukup nyaman."];
        }

        return $r->temperature > 30
            ? ['title' => 'Suhu ruangan agak panas', 'description' => "Suhu saat ini {$t}°C. Nyalakan kipas agar ruangan lebih nyaman."]
            : ['title' => 'Kondisi ruangan nyaman', 'description' => "Suhu saat ini {$t}°C dan masih berada dalam rentang aman."];
    }

    private function details(?SensorReading $r, bool $fanOn, bool $lampOn): array
    {
        if (! $r) {
            return [];
        }

        $temperature = Status::temperature($r->temperature);
        $humidity = Status::humidity($r->humidity);
        $light = Status::light($r->light);
        $air = Status::air($r->air_quality);

        return [
            [
                'label' => 'Suhu', 'value' => number_format($r->temperature, 1).'°C',
                'range' => $fanOn ? 'Kipas aktif · pemantauan live' : 'Aman: 20–30°C',
                'state' => $temperature['label'], 'tone' => $temperature['tone'], 'icon' => $temperature['icon'],
            ],
            [
                'label' => 'Kelembapan', 'value' => $r->humidity.'%',
                'range' => 'Aman: 40–70%',
                'state' => $humidity['label'], 'tone' => $humidity['tone'], 'icon' => $humidity['icon'],
            ],
            [
                'label' => 'Cahaya', 'value' => $r->light.' lx',
                'range' => $lampOn ? 'Lampu utama aktif' : 'Lampu utama nonaktif',
                'state' => $light['label'], 'tone' => $light['tone'], 'icon' => $light['icon'],
            ],
            [
                'label' => 'Kualitas Udara', 'value' => $air['label'],
                'range' => 'Indeks: '.$r->air_quality.' AQI',
                'state' => $air['label'], 'tone' => $air['tone'], 'icon' => $air['icon'],
            ],
        ];
    }
}
