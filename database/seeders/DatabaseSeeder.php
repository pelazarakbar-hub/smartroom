<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal untuk mencoba SmartRoom.
     * PENTING: ganti password kedua akun ini (atau hapus akunnya) sebelum website dipublikasikan.
     */
    public function run(): void
    {
        // Di server publik, akun contoh dengan password bawaan tidak dibuat.
        // Isi SEED_ADMIN_PASSWORD dan SEED_USER_PASSWORD di .env bila tetap ingin membuatnya.
        if (app()->isProduction() && ! env('SEED_ADMIN_PASSWORD')) {
            $this->command?->warn('Seeder dilewati di production. Isi SEED_ADMIN_PASSWORD di .env untuk membuat akun admin.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'admin@smartroom.test')],
            [
                'name' => 'Admin SmartRoom',
                'password' => env('SEED_ADMIN_PASSWORD', 'Admin12345'),
                'role' => 'admin',
            ]
        );

        $demo = User::updateOrCreate(
            ['email' => env('SEED_USER_EMAIL', 'user@smartroom.test')],
            [
                'name' => 'Pengguna Demo',
                'password' => env('SEED_USER_PASSWORD', env('SEED_ADMIN_PASSWORD') ? bin2hex(random_bytes(8)) : 'User12345'),
                'role' => 'user',
            ]
        );

        $room = Room::firstOrCreate(
            ['name' => 'Kamar Utama'],
            ['description' => 'Ruangan contoh', 'auto_join' => true]
        );

        if ($room->devices()->count() === 0) {
            $room->devices()->create([
                'name' => 'Lampu Utama',
                'type' => 'lamp',
                'is_on' => true,
                'is_online' => true,
                'last_seen_at' => now(),
            ]);
            $room->devices()->create([
                'name' => 'Kipas Pintar',
                'type' => 'fan',
                'is_on' => false,
                'level' => 2,
                'is_online' => true,
                'last_seen_at' => now(),
            ]);
        }

        $room->users()->syncWithoutDetaching([$demo->id]);

        if ($room->readings()->count() === 0) {
            $this->seedReadings($room);
        }

        $this->command?->info('Akun admin: '.$admin->email.' | akun demo: '.$demo->email);
    }

    /**
     * Data sensor 24 jam terakhir (tiap 15 menit) dengan pola suhu dan cahaya harian.
     */
    private function seedReadings(Room $room): void
    {
        $start = now()->subHours(24)->startOfMinute();
        $rows = [];

        for ($i = 0; $i <= 96; $i++) {
            $time = $start->copy()->addMinutes($i * 15);
            $hour = (float) $time->format('G') + $time->minute / 60;

            $heat = max(0.0, sin(($hour - 8) / 12 * M_PI));  // puncak sekitar pukul 14.00
            $daylight = max(0.0, sin(($hour - 6) / 12 * M_PI)); // puncak sekitar pukul 12.00

            $rows[] = [
                'room_id' => $room->id,
                'temperature' => round(28.5 + 3.5 * $heat, 1),
                'humidity' => (int) round(67 - 4 * $heat),
                'light' => (int) round(180 + 340 * $daylight),
                'occupants' => 1,
                'air_quality' => 42,
                'recorded_at' => $time->toDateTimeString(),
            ];
        }

        SensorReading::insert($rows);
    }
}
