<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_melihat_halaman_depan(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ruang cerdas, hidup lebih nyaman')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false);
    }

    public function test_pengguna_yang_sudah_masuk_diarahkan_ke_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }
}
