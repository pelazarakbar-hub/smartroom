<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Room;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartRoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman memakai @vite (Alpine.js); tes tidak butuh hasil build.
        $this->withoutVite();
    }

    private function user(string $role = 'user'): User
    {
        return User::create([
            'name' => 'Tester '.$role,
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    private function roomWithFan(?User $member = null): array
    {
        $room = Room::create(['name' => 'Kamar Uji']);
        $fan = $room->devices()->create(['name' => 'Kipas', 'type' => 'fan', 'is_on' => false, 'is_online' => true]);
        $lamp = $room->devices()->create(['name' => 'Lampu', 'type' => 'lamp', 'is_on' => true, 'is_online' => true]);

        if ($member) {
            $room->users()->attach($member->id);
        }

        return [$room, $fan, $lamp];
    }

    public function test_pendaftaran_selalu_menjadi_user_walau_role_dikirim(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'Budi@Example.com',
            'password' => 'rahasia123',
            'role' => 'admin',
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'role' => 'user']);
    }

    public function test_password_kurang_dari_8_karakter_ditolak(): void
    {
        $this->post('/register', ['name' => 'Budi', 'email' => 'b@example.com', 'password' => 'pendek'])
            ->assertSessionHasErrors('password');
    }

    public function test_login_mengarahkan_sesuai_peran(): void
    {
        $user = $this->user('user');
        $admin = $this->user('admin');

        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/beranda');
        $this->post('/logout');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password123'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/admin');
    }

    public function test_login_salah_ditolak(): void
    {
        $user = $this->user();

        $this->post('/login', ['email' => $user->email, 'password' => 'salah-salah'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_login_dan_user_tidak_boleh_masuk_admin(): void
    {
        $this->get('/beranda')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');

        $this->actingAs($this->user('user'))->get('/admin')->assertForbidden();
        $this->actingAs($this->user('admin'))->get('/beranda')->assertForbidden();
    }

    public function test_user_melihat_semua_halaman_aplikasi(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        SensorReading::create([
            'room_id' => $room->id, 'temperature' => 31.2, 'humidity' => 65, 'light' => 420,
            'occupants' => 1, 'air_quality' => 42, 'recorded_at' => now(),
        ]);

        foreach (['/beranda', '/ruangan', '/perangkat', '/insight', '/profil', '/riwayat'] as $path) {
            $this->actingAs($user)->get($path)->assertOk();
        }

        $this->actingAs($user)->get('/beranda')->assertSee('31.2°C');
    }

    public function test_user_tanpa_ruangan_melihat_pesan_kosong(): void
    {
        $this->actingAs($this->user())->get('/beranda')->assertOk()->assertSee('Belum ada ruangan');
    }

    public function test_user_bisa_menyalakan_perangkat_di_ruangannya_saja(): void
    {
        $user = $this->user();
        [, $fan] = $this->roomWithFan($user);
        [, $otherFan] = $this->roomWithFan(); // ruangan lain, user tidak terdaftar

        $this->actingAs($user)->postJson("/perangkat/{$fan->id}/toggle", ['on' => true])
            ->assertOk()->assertJson(['is_on' => true]);
        $this->assertTrue($fan->fresh()->is_on);
        $this->assertDatabaseHas('device_logs', ['device_id' => $fan->id, 'user_id' => $user->id]);

        $this->actingAs($user)->postJson("/perangkat/{$otherFan->id}/toggle", ['on' => true])->assertForbidden();
        $this->assertFalse($otherFan->fresh()->is_on);
    }

    public function test_kecepatan_kipas_hanya_bisa_diatur_saat_kipas_menyala(): void
    {
        $user = $this->user();
        [, $fan] = $this->roomWithFan($user);

        $this->actingAs($user)->postJson("/perangkat/{$fan->id}/kecepatan", ['level' => 3])->assertStatus(422);

        $fan->update(['is_on' => true]);
        $this->actingAs($user)->postJson("/perangkat/{$fan->id}/kecepatan", ['level' => 3])->assertOk();
        $this->assertSame(3, $fan->fresh()->level);
    }

    public function test_api_perangkat_butuh_kunci_ruangan(): void
    {
        $this->postJson('/api/device/readings', ['temperature' => 30])->assertUnauthorized();
        $this->postJson('/api/device/readings', ['temperature' => 30], ['X-Room-Key' => 'salah'])->assertUnauthorized();
    }

    public function test_api_menyimpan_data_dan_aturan_otomatis_menyalakan_kipas(): void
    {
        [$room, $fan] = $this->roomWithFan();
        $room->update(['auto_fan_above' => 30]);

        $this->postJson('/api/device/readings', ['temperature' => 31.5, 'humidity' => 60, 'light' => 300], ['X-Room-Key' => $room->api_key])
            ->assertCreated();

        $this->assertDatabaseHas('sensor_readings', ['room_id' => $room->id, 'humidity' => 60]);
        $this->assertTrue($fan->fresh()->is_on);

        $this->getJson('/api/device/state', ['X-Room-Key' => $room->api_key])
            ->assertOk()->assertJsonPath('devices.0.id', $fan->id);
    }

    public function test_admin_mengelola_ruangan_perangkat_dan_pengguna(): void
    {
        $admin = $this->user('admin');
        $user = $this->user('user');

        $this->actingAs($admin)->post('/admin/ruangan', ['name' => 'Ruang Baru', 'users' => [$user->id]])->assertRedirect();
        $room = Room::where('name', 'Ruang Baru')->firstOrFail();
        $this->assertTrue($room->users()->whereKey($user->id)->exists());
        $this->assertNotEmpty($room->api_key);

        $this->actingAs($admin)->post('/admin/perangkat', ['room_id' => $room->id, 'name' => 'Lampu', 'type' => 'lamp', 'is_online' => 1])
            ->assertRedirect('/admin/perangkat');
        $this->assertDatabaseHas('devices', ['name' => 'Lampu', 'room_id' => $room->id]);

        foreach (['/admin', '/admin/pengguna', '/admin/ruangan', '/admin/perangkat', "/admin/ruangan/{$room->id}/ubah"] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }

        $this->actingAs($admin)->patch("/admin/pengguna/{$user->id}/peran", ['role' => 'admin'])->assertRedirect();
        $this->assertSame('admin', $user->fresh()->role);

        // Admin tidak bisa mengubah atau menghapus akunnya sendiri.
        $this->actingAs($admin)->delete("/admin/pengguna/{$admin->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_pengguna_baru_otomatis_masuk_ruangan_auto_join(): void
    {
        $room = Room::create(['name' => 'Demo', 'auto_join' => true]);

        $this->post('/register', ['name' => 'Siti', 'email' => 'siti@example.com', 'password' => 'rahasia123']);

        $this->assertTrue(User::where('email', 'siti@example.com')->firstOrFail()->rooms()->whereKey($room->id)->exists());
    }
}
