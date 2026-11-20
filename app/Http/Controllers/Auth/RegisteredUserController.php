<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menimpa RegisteredUserController bawaan Breeze, supaya sesuai desain Figma:
 * formulir hanya berisi nama, email, dan password (tanpa ulangi password),
 * lalu pengguna diarahkan ke halaman masuk dengan pesan berhasil.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
        ]);

        // Peran selalu "user"; peran admin hanya bisa diberikan oleh admin.
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'user',
        ]);

        event(new Registered($user));

        // Ruangan bertanda "otomatis untuk pengguna baru" langsung dapat diakses.
        $user->rooms()->attach(Room::where('auto_join', true)->pluck('id')->all());

        return redirect()
            ->route('login')
            ->with('status', 'Pendaftaran berhasil. Silakan masuk dengan email dan password yang baru didaftarkan.');
    }
}
