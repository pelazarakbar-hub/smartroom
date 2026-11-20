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
        // Hanya suhu yang wajib. Sensor yang tidak mengirim data disimpan kosong (NULL),
        // bukan angka karangan, supaya aplikasi tidak menampilkan sensor yang sebenarnya tidak ada.
        $reading = $room->readings()->create([
            'temperature' => round((float) $data['temperature'], 1),
            'humidity' => isset($data['humidity']) ? (int) $data['humidity'] : null,
            'light' => isset($data['light']) ? (int) $data['light'] : null,
            'occupants' => isset($data['occupants']) ? (int) $data['occupants'] : null,
            'air_quality' => isset($data['air_quality']) ? (int) $data['air_quality'] : null,
            'recorded_at' => now(),
        ]);

        // Setiap kiriman data dianggap tanda perangkat di ruangan ini masih hidup.
        $room->markDevicesSeen();

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
