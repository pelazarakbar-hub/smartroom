<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Services\ReadingService;
use Illuminate\Console\Command;

class SimulateSensors extends Command
{
    protected $signature = 'smartroom:simulate
                            {--room= : ID ruangan (default: semua ruangan)}
                            {--interval=10 : Jeda dalam detik antar data}
                            {--once : Kirim satu data saja lalu berhenti}';

    protected $description = 'Mensimulasikan data sensor SmartRoom sebagai pengganti perangkat sungguhan';

    public function handle(ReadingService $readings): int
    {
        $interval = max(1, (int) $this->option('interval'));

        $this->info('Simulasi sensor berjalan. Tekan Ctrl+C untuk berhenti.');

        while (true) {
            $rooms = $this->option('room')
                ? Room::whereKey($this->option('room'))->get()
                : Room::all();

            if ($rooms->isEmpty()) {
                $this->warn('Belum ada ruangan. Jalankan: php artisan db:seed');

                return self::FAILURE;
            }

            foreach ($rooms as $room) {
                $this->tick($room, $readings);
            }

            if ($this->option('once')) {
                return self::SUCCESS;
            }

            sleep($interval);
        }
    }

    private function tick(Room $room, ReadingService $readings): void
    {
        $room->load('devices');

        $last = $room->latestReading();
        $fan = $room->devices->firstWhere('type', 'fan');
        $lamp = $room->devices->firstWhere('type', 'lamp');

        // Suhu bergerak pelan menuju target: turun saat kipas menyala, naik saat mati.
        $temperature = $last?->temperature ?? 31.0;
        $target = ($fan && $fan->is_on) ? 29.3 - (($fan->level - 1) * 0.8) : 32.0;
        $difference = $target - $temperature;
        $temperature = abs($difference) < 0.1
            ? $target
            : $temperature + (($difference <=> 0) * min(abs($difference), 0.3));
        $temperature += random_int(-2, 2) / 20;

        // Cahaya mengikuti lampu.
        $light = $last?->light ?? 420;
        $lightTarget = ($lamp && $lamp->is_on) ? 520 : 180;
        $lightDifference = $lightTarget - $light;
        $light = abs($lightDifference) < 10
            ? $lightTarget
            : $light + (($lightDifference <=> 0) * min(abs($lightDifference), 40));

        $humidity = max(55, min(72, ($last?->humidity ?? 65) + random_int(-1, 1)));

        $reading = $readings->record($room, [
            'temperature' => $temperature,
            'humidity' => $humidity,
            'light' => (int) round($light),
            'occupants' => 1,
            'air_quality' => 40 + random_int(0, 6),
        ]);

        $this->line(sprintf(
            '[%s] %s: %.1f°C, %d%%, %d lx',
            now()->format('H:i:s'),
            $room->name,
            $reading->temperature,
            $reading->humidity,
            $reading->light
        ));
    }
}
