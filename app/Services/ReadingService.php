<?php

namespace App\Services;

use App\Models\Room;
use App\Models\SensorReading;

/**
 * Satu pintu untuk menyimpan data sensor, dipakai oleh API perangkat
 * dan oleh simulator (php artisan smartroom:simulate).
 */
class ReadingService
{
    /**
     * @param  array{temperature: float|int|string, humidity?: int|null, light?: int|null, occupants?: int|null, air_quality?: int|null}  $data
     */
    public function record(Room $room, array $data): SensorReading
    {
        // Nilai yang tidak dikirim perangkat memakai data terakhir, bukan nol.
        $previous = $room->latestReading();

        $reading = $room->readings()->create([
            'temperature' => round((float) $data['temperature'], 1),
            'humidity' => (int) ($data['humidity'] ?? $previous?->humidity ?? 0),
            'light' => (int) ($data['light'] ?? $previous?->light ?? 0),
            'occupants' => (int) ($data['occupants'] ?? $previous?->occupants ?? 0),
            'air_quality' => (int) ($data['air_quality'] ?? $previous?->air_quality ?? 40),
            'recorded_at' => now(),
        ]);

        // Setiap kiriman data dianggap tanda perangkat di ruangan ini masih hidup.
        $room->devices()->update(['is_online' => true, 'last_seen_at' => now()]);

        $this->applyAutomation($room, $reading);

        return $reading;
    }

    /**
     * Aturan otomatis dari AI Insight: kipas menyala saat suhu melewati batas.
     */
    private function applyAutomation(Room $room, SensorReading $reading): void
    {
        if ($room->auto_fan_above === null || $reading->temperature < $room->auto_fan_above) {
            return;
        }

        $room->devices()
            ->where('type', 'fan')
            ->where('is_on', false)
            ->get()
            ->each(fn ($fan) => $fan->turn(true, null, true));
    }
}
