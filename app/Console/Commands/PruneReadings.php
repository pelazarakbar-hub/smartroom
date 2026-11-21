<?php

namespace App\Console\Commands;

use App\Models\SensorReading;
use Illuminate\Console\Command;

class PruneReadings extends Command
{
    protected $signature = 'smartroom:prune-readings {--days=30 : Hapus data sensor yang lebih lama dari sekian hari}';

    protected $description = 'Menghapus data sensor lama agar database tidak terus membesar';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $deleted = SensorReading::where('recorded_at', '<', now()->subDays($days))->delete();

        $this->info("Selesai: {$deleted} data sensor lebih lama dari {$days} hari dihapus.");

        return self::SUCCESS;
    }
}
