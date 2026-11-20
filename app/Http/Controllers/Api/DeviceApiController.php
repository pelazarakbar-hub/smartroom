<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\ReadingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API untuk perangkat sungguhan (ESP32, Wemos, dsb.).
 *
 * Autentikasi: kunci ruangan (lihat halaman Admin > Ruangan > Ubah), dikirim lewat
 * header "X-Room-Key: <kunci>" atau "Authorization: Bearer <kunci>".
 *
 *   POST /api/device/readings  kirim data sensor
 *   GET  /api/device/state     ambil status perangkat yang diminta pengguna (nyala/mati, level)
 *   POST /api/device/state     laporkan status perangkat yang sebenarnya
 */
class DeviceApiController extends Controller
{
    public function storeReading(Request $request, ReadingService $readings): JsonResponse
    {
        $room = $this->room($request);

        $data = $request->validate([
            'temperature' => ['required', 'numeric', 'between:-20,80'],
            'humidity' => ['nullable', 'integer', 'between:0,100'],
            'light' => ['nullable', 'integer', 'between:0,100000'],
            'occupants' => ['nullable', 'integer', 'between:0,100'],
            'air_quality' => ['nullable', 'integer', 'between:0,500'],
        ]);

        $reading = $readings->record($room, $data);

        return response()->json(['ok' => true, 'recorded_at' => $reading->recorded_at->toIso8601String()], 201);
    }

    public function state(Request $request): JsonResponse
    {
        $room = $this->room($request);

        return response()->json([
            'room' => $room->name,
            'auto_fan_above' => $room->auto_fan_above,
            'devices' => $room->devices()->get()->map(fn ($device) => [
                'id' => $device->id,
                'name' => $device->name,
                'type' => $device->type,
                'is_on' => $device->is_on,
                'level' => $device->level,
            ])->values(),
        ]);
    }

    public function reportState(Request $request): JsonResponse
    {
        $room = $this->room($request);

        $data = $request->validate([
            'devices' => ['required', 'array', 'max:50'],
            'devices.*.id' => ['required', 'integer'],
            'devices.*.is_on' => ['required', 'boolean'],
            'devices.*.level' => ['nullable', 'integer', 'between:1,3'],
        ]);

        $devices = $room->devices()->get()->keyBy('id');

        foreach ($data['devices'] as $item) {
            $device = $devices->get($item['id']);

            if (! $device) {
                continue;
            }

            $device->turn((bool) $item['is_on']);

            if (isset($item['level'])) {
                $device->setLevel((int) $item['level']);
            }

            $device->update(['is_online' => true, 'last_seen_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    private function room(Request $request): Room
    {
        $key = $request->header('X-Room-Key') ?: $request->bearerToken();

        $room = $key ? Room::where('api_key', $key)->first() : null;

        abort_unless($room, 401, 'Kunci ruangan tidak valid.');

        return $room;
    }
}
