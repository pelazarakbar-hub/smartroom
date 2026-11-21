<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CheckPublication extends Command
{
    protected $signature = 'smartroom:cek-publikasi';

    protected $description = 'Memeriksa apakah SmartRoom aman untuk dipublikasikan';

    /** Akun contoh dari seeder dan password bawaannya. */
    private const DEMO = [
        'admin@smartroom.test' => 'Admin12345',
        'user@smartroom.test' => 'User12345',
    ];

    public function handle(): int
    {
        $masalah = [];

        if (! app()->isProduction()) {
            $masalah[] = 'APP_ENV belum "production" (sekarang: '.config('app.env').').';
        }
        if (config('app.debug')) {
            $masalah[] = 'APP_DEBUG masih true. Ubah menjadi false agar detail error tidak terlihat pengunjung.';
        }
        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $masalah[] = 'APP_URL belum memakai https://.';
        }
        if (empty(config('app.key'))) {
            $masalah[] = 'APP_KEY kosong. Jalankan: php artisan key:generate';
        }

        foreach (self::DEMO as $email => $password) {
            $user = User::where('email', $email)->first();
            if ($user && Hash::check($password, $user->password)) {
                $masalah[] = "Akun {$email} masih memakai password bawaan. Ganti passwordnya atau hapus akunnya.";
            }
        }

        if (! $masalah) {
            $this->info('Semua pemeriksaan lolos.');

            return self::SUCCESS;
        }

        foreach ($masalah as $m) {
            $this->warn('- '.$m);
        }
        $this->newLine();
        $this->error(count($masalah).' hal perlu diperbaiki sebelum dipublikasikan.');

        return self::FAILURE;
    }
}
