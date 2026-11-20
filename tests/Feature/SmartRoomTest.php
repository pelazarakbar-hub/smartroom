<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Room;
use App\Models\SensorReading;
use App\Models\User;
use App\Services\InsightService;
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

    public function test_perangkat_yang_lama_diam_dianggap_offline_dan_tidak_bisa_dikontrol(): void
    {
        $user = $this->user();
        [$room, $fan, $lamp] = $this->roomWithFan($user);

        // Fan pernah melapor 10 menit lalu; lampu belum pernah melapor (perangkat manual).
        $fan->update(['last_seen_at' => now()->subMinutes(10)]);

        $this->assertFalse($fan->fresh()->isOnline());
        $this->assertTrue($lamp->fresh()->isOnline());
        $this->assertSame(1, $room->devices()->online()->count());

        $this->actingAs($user)->postJson("/perangkat/{$fan->id}/toggle", ['on' => true])->assertStatus(422);
        $this->assertFalse($fan->fresh()->is_on);
    }

    public function test_perangkat_yang_baru_melapor_dianggap_online(): void
    {
        [, $fan] = $this->roomWithFan();
        $fan->update(['last_seen_at' => now()->subSeconds(30)]);

        $this->assertTrue($fan->fresh()->isOnline());
    }

    public function test_saklar_manual_offline_mengalahkan_data_terbaru(): void
    {
        [, $fan] = $this->roomWithFan();
        $fan->update(['is_online' => false, 'last_seen_at' => now()]);

        $this->assertFalse($fan->fresh()->isOnline());
    }

    public function test_data_sensor_dan_pengecekan_status_membuat_perangkat_online_lagi(): void
    {
        [$room, $fan, $lamp] = $this->roomWithFan();
        $fan->update(['last_seen_at' => now()->subMinutes(10)]);
        $lamp->update(['last_seen_at' => now()->subMinutes(10)]);

        $this->postJson('/api/device/readings', ['temperature' => 28], ['X-Room-Key' => $room->api_key])->assertCreated();
        $this->assertTrue($fan->fresh()->isOnline());

        $fan->update(['last_seen_at' => now()->subMinutes(10)]);
        $this->getJson('/api/device/state', ['X-Room-Key' => $room->api_key])->assertOk();
        $this->assertTrue($fan->fresh()->isOnline());
    }

    public function test_admin_mencentang_online_menandai_perangkat_online_manual(): void
    {
        $admin = $this->user('admin');
        [, $fan] = $this->roomWithFan();
        $fan->update(['last_seen_at' => now()->subMinutes(10)]);

        $this->actingAs($admin)->put("/admin/perangkat/{$fan->id}", [
            'room_id' => $fan->room_id, 'name' => 'Kipas', 'type' => 'fan', 'is_online' => 1,
        ])->assertRedirect();

        $this->assertTrue($fan->fresh()->isOnline());
    }

    // ------------------------------------------------------------ AI Insight

    private function reading(Room $room, array $values = [], int $minutesAgo = 1): SensorReading
    {
        return $room->readings()->create($values + [
            'temperature' => 26,
            'humidity' => 55,
            'light' => 400,
            'occupants' => 1,
            'air_quality' => 30,
            'recorded_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    private function analysis(Room $room, User $user): array
    {
        return app(InsightService::class)->analyze($room->fresh(), $user);
    }

    private function keys(array $analysis): array
    {
        return array_column($analysis['insights'], 'key');
    }

    public function test_insight_tanpa_data_hanya_menyarankan_memeriksa_sensor(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);

        $analysis = $this->analysis($room, $user);

        $this->assertNull($analysis['score']);
        $this->assertSame(['sensor'], $this->keys($analysis));
        $this->assertSame('high', $analysis['insights'][0]['severity']);
    }

    public function test_insight_menahan_saran_bila_data_sensor_sudah_lama(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        $this->reading($room, ['temperature' => 35], 30); // 30 menit lalu: dianggap basi

        $analysis = $this->analysis($room, $user);

        $this->assertNull($analysis['score']);
        $this->assertSame(['sensor'], $this->keys($analysis));
    }

    public function test_insight_kondisi_normal_bernilai_nyaman_tanpa_peringatan(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        foreach ([60, 40, 20, 5, 1] as $minutes) {
            $this->reading($room, [], $minutes);
        }

        $analysis = $this->analysis($room, $user);

        $this->assertGreaterThanOrEqual(80, $analysis['score']);
        $this->assertSame('Nyaman', $analysis['label']);
        $this->assertNotContains('humidity', $this->keys($analysis));
        $this->assertNotContains('air', $this->keys($analysis));
        $this->assertNotContains('energy', $this->keys($analysis));
        $this->assertSame('ok', $analysis['insights'][0]['severity']);
    }

    public function test_insight_suhu_tinggi_menjadi_prioritas_pertama_dan_menurunkan_skor(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        foreach ([80, 60, 40, 20, 10, 5, 1] as $minutes) {
            $this->reading($room, ['temperature' => 32], $minutes);
        }

        $analysis = $this->analysis($room, $user);

        $this->assertLessThan(80, $analysis['score']);
        $this->assertSame('fan', $analysis['insights'][0]['key']);
        $this->assertSame('high', $analysis['insights'][0]['severity']);
        $this->assertTrue($analysis['insights'][0]['actionable']);
    }

    public function test_insight_mendeteksi_suhu_yang_sedang_naik_sebelum_melewati_batas(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        foreach ([40, 35, 30, 25, 20] as $minutes) {
            $this->reading($room, ['temperature' => 26], $minutes);
        }
        foreach ([10, 8, 6, 4, 2] as $minutes) {
            $this->reading($room, ['temperature' => 29], $minutes);
        }

        $fan = collect($this->analysis($room, $user)['insights'])->firstWhere('key', 'fan');

        $this->assertSame('Suhu mulai naik', $fan['title']);
        $this->assertSame('medium', $fan['severity']);
    }

    public function test_insight_kelembapan_dan_kualitas_udara_di_luar_batas_muncul(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        $this->reading($room, ['humidity' => 30, 'air_quality' => 150]);

        $analysis = $this->analysis($room, $user);
        $byKey = collect($analysis['insights'])->keyBy('key');

        $this->assertSame('medium', $byKey['humidity']['severity']);
        $this->assertSame('high', $byKey['air']['severity']);
        $this->assertSame('air', $analysis['insights'][0]['key']); // paling penting di urutan pertama
    }

    public function test_saran_hemat_energi_mematikan_perangkat_saat_ruangan_kosong(): void
    {
        $user = $this->user();
        [$room, , $lamp] = $this->roomWithFan($user); // lampu menyala
        $this->reading($room, ['occupants' => 0]);

        $this->assertContains('energy', $this->keys($this->analysis($room, $user)));

        $this->actingAs($user)->postJson('/insight/energy/terapkan')
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertFalse($lamp->fresh()->is_on);
        $this->assertDatabaseHas('device_logs', ['device_id' => $lamp->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('insight_feedbacks', ['user_id' => $user->id, 'insight_key' => 'energy', 'applied' => true]);

        // Sudah mati semua: saran tidak muncul lagi dan penerapan ulang ditolak.
        $this->assertNotContains('energy', $this->keys($this->analysis($room, $user)));
        $this->actingAs($user)->postJson('/insight/energy/terapkan')->assertStatus(422);
    }

    public function test_saran_hemat_energi_tidak_muncul_saat_ruangan_terisi(): void
    {
        $user = $this->user();
        [$room, , $lamp] = $this->roomWithFan($user);
        $this->reading($room, ['occupants' => 2]);

        $this->assertNotContains('energy', $this->keys($this->analysis($room, $user)));
        $this->actingAs($user)->postJson('/insight/energy/terapkan')->assertStatus(422);
        $this->assertTrue($lamp->fresh()->is_on);
    }

    public function test_umpan_balik_insight_menerima_kunci_baru_dan_menolak_yang_tidak_dikenal(): void
    {
        $user = $this->user();
        $this->roomWithFan($user);

        $this->actingAs($user)->postJson('/insight/air/umpan-balik', ['helpful' => true])->assertOk();
        $this->assertDatabaseHas('insight_feedbacks', ['user_id' => $user->id, 'insight_key' => 'air', 'helpful' => true]);

        $this->actingAs($user)->postJson('/insight/ngawur/umpan-balik', ['helpful' => true])->assertNotFound();
        $this->actingAs($user)->postJson('/insight/ngawur/terapkan')->assertNotFound();
    }

    public function test_halaman_insight_menampilkan_skor_kenyamanan(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        $this->reading($room);

        $this->actingAs($user)->get('/insight')
            ->assertOk()
            ->assertSee('Skor kenyamanan')
            ->assertSee('Prioritas Utama');
    }

    // ------------------------------------------------------------ sensor opsional

    public function test_api_hanya_butuh_suhu_dan_tidak_mengarang_sensor_lain(): void
    {
        [$room] = $this->roomWithFan();

        $this->postJson('/api/device/readings', ['temperature' => 29.5], ['X-Room-Key' => $room->api_key])->assertCreated();

        $this->assertDatabaseHas('sensor_readings', [
            'room_id' => $room->id,
            'temperature' => 29.5,
            'humidity' => null,
            'light' => null,
            'occupants' => null,
            'air_quality' => null,
        ]);
    }

    public function test_halaman_ruangan_dan_beranda_hanya_menampilkan_sensor_yang_ada(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        // Perangkat dengan DHT11 saja: suhu dan kelembapan.
        $room->readings()->create(['temperature' => 27.5, 'humidity' => 60, 'recorded_at' => now()]);

        $this->actingAs($user)->get('/ruangan')
            ->assertOk()
            ->assertSee('Kelembapan')
            ->assertDontSee('Kualitas Udara')
            ->assertDontSee('Indeks:');

        $this->actingAs($user)->get('/beranda')
            ->assertOk()
            ->assertSee('Kelembapan')
            ->assertDontSee('Intensitas Cahaya')
            ->assertDontSee('Keberadaan');
    }

    public function test_halaman_ruangan_menampilkan_kualitas_udara_bila_sensornya_mengirim_data(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        $room->readings()->create(['temperature' => 27.5, 'humidity' => 60, 'air_quality' => 35, 'recorded_at' => now()]);

        $this->actingAs($user)->get('/ruangan')->assertOk()->assertSee('Kualitas Udara');
    }

    public function test_insight_tetap_berjalan_hanya_dengan_data_suhu(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);
        $room->readings()->create(['temperature' => 26.5, 'recorded_at' => now()->subMinute()]);

        $analysis = $this->analysis($room, $user);
        $keys = $this->keys($analysis);

        $this->assertSame(100, $analysis['score']);
        $this->assertStringContainsString('Suhu', $analysis['headline']);
        $this->assertStringNotContainsString('kelembapan', $analysis['headline']);
        $this->assertStringNotContainsString('kualitas udara', $analysis['headline']);
        foreach (['air', 'humidity', 'lamp', 'energy'] as $absent) {
            $this->assertNotContains($absent, $keys);
        }
        $this->assertContains('sensor', $keys);
        $this->actingAs($user)->get('/insight')->assertOk();
    }

    public function test_saran_hemat_energi_tidak_muncul_tanpa_sensor_keberadaan(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user); // lampu menyala
        $room->readings()->create(['temperature' => 26, 'recorded_at' => now()->subMinute()]);

        $this->assertNotContains('energy', $this->keys($this->analysis($room, $user)));
        $this->actingAs($user)->postJson('/insight/energy/terapkan')->assertStatus(422);
    }

    // ------------------------------------------------------------ umpan balik bisa dibatalkan

    public function test_umpan_balik_bisa_dibatalkan_dan_diganti(): void
    {
        $user = $this->user();
        $this->roomWithFan($user);
        $where = ['user_id' => $user->id, 'insight_key' => 'fan'];

        $this->actingAs($user)->postJson('/insight/fan/umpan-balik', ['helpful' => true])->assertOk();
        $this->assertDatabaseHas('insight_feedbacks', $where + ['helpful' => true]);

        $this->actingAs($user)->postJson('/insight/fan/umpan-balik', ['helpful' => null])->assertOk();
        $this->assertDatabaseHas('insight_feedbacks', $where + ['helpful' => null]);

        $this->actingAs($user)->postJson('/insight/fan/umpan-balik', ['helpful' => false])->assertOk();
        $this->assertDatabaseHas('insight_feedbacks', $where + ['helpful' => false]);

        // Kolom helpful wajib ada (boleh null), tetapi nilai ngawur ditolak.
        $this->actingAs($user)->postJson('/insight/fan/umpan-balik', [])->assertStatus(422);
        $this->actingAs($user)->postJson('/insight/fan/umpan-balik', ['helpful' => 'mungkin'])->assertStatus(422);
    }

    public function test_pembatalan_umpan_balik_tidak_menghapus_status_saran_diterapkan(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);

        $this->actingAs($user)->postJson('/insight/fan/terapkan')->assertOk();
        $this->actingAs($user)->postJson('/insight/fan/umpan-balik', ['helpful' => true])->assertOk();
        $this->actingAs($user)->postJson('/insight/fan/umpan-balik', ['helpful' => null])->assertOk();

        $this->assertDatabaseHas('insight_feedbacks', ['user_id' => $user->id, 'insight_key' => 'fan', 'applied' => true, 'helpful' => null]);
        $this->assertNotNull($room->fresh()->auto_fan_above);
    }

    // ------------------------------------------------------------ label perangkat

    public function test_status_koneksi_perangkat_live_simulasi_atau_offline(): void
    {
        [, $fan, $lamp] = $this->roomWithFan();

        $this->assertSame('simulated', $fan->fresh()->connectionState()); // belum pernah melapor

        $fan->update(['last_seen_at' => now()->subSeconds(30)]);
        $this->assertSame('live', $fan->fresh()->connectionState());

        $fan->update(['last_seen_at' => now()->subMinutes(10)]);
        $this->assertSame('offline', $fan->fresh()->connectionState());

        $lamp->update(['is_online' => false]);
        $this->assertSame('offline', $lamp->fresh()->connectionState());
    }

    public function test_halaman_perangkat_menandai_perangkat_contoh_sebagai_simulasi(): void
    {
        $user = $this->user();
        [$room] = $this->roomWithFan($user);

        $this->actingAs($user)->get('/perangkat')
            ->assertOk()
            ->assertSee('Mode simulasi')
            ->assertSee('Simulasi · belum tersambung ke alat')
            ->assertDontSee('Online · tersinkron');

        // Begitu alat sungguhan mengirim data, statusnya berubah menjadi live.
        $this->postJson('/api/device/readings', ['temperature' => 28], ['X-Room-Key' => $room->api_key])->assertCreated();

        $this->actingAs($user)->get('/perangkat')
            ->assertOk()
            ->assertSee('Online · tersinkron')
            ->assertDontSee('Mode simulasi');
    }
}
