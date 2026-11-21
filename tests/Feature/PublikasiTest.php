<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_lupa_password_memakai_tampilan_smartroom(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Kirim tautan reset')
            ->assertSee('login-screen', false);
    }

    public function test_login_punya_tautan_lupa_password(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('password.request'), false);
    }

    public function test_prune_hanya_menghapus_data_lama(): void
    {
        $room = Room::create(['name' => 'Uji', 'description' => '']);

        $old = SensorReading::create(['room_id' => $room->id, 'temperature' => 27, 'recorded_at' => now()->subDays(45)]);
        $new = SensorReading::create(['room_id' => $room->id, 'temperature' => 28, 'recorded_at' => now()->subDays(2)]);

        $this->artisan('smartroom:prune-readings', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseMissing('sensor_readings', ['id' => $old->id]);
        $this->assertDatabaseHas('sensor_readings', ['id' => $new->id]);
    }

    public function test_cek_publikasi_menandai_akun_demo_berpassword_bawaan(): void
    {
        User::create(['name' => 'Admin', 'email' => 'admin@smartroom.test', 'password' => 'Admin12345', 'role' => 'admin']);

        $this->artisan('smartroom:cek-publikasi')
            ->expectsOutputToContain('admin@smartroom.test')
            ->assertFailed();
    }

    public function test_cek_publikasi_lolos_bila_semua_aman(): void
    {
        config(['app.env' => 'production', 'app.debug' => false, 'app.url' => 'https://smartroom.example.com']);
        $this->app['env'] = 'production';

        User::create(['name' => 'Admin', 'email' => 'admin@smartroom.test', 'password' => 'KataSandiBaruYangKuat#1', 'role' => 'admin']);

        $this->artisan('smartroom:cek-publikasi')->assertSuccessful();
    }
}
