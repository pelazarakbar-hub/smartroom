<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceController as AdminDeviceController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

// ---------------------------------------------------------------- sudah login
Route::middleware('auth')->group(function () {
    // Nama route "dashboard" dipakai Breeze setelah login. Di sini diarahkan sesuai peran.
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'app.home'))
        ->name('dashboard');

    // Pengaturan akun bawaan Breeze (email, password, hapus akun).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // -------- Pengguna
    Route::middleware('role:user')->controller(AppController::class)->group(function () {
        Route::get('/beranda', 'home')->name('app.home');
        Route::get('/ruangan', 'room')->name('app.room');
        Route::post('/ruangan/pilih', 'selectRoom')->name('app.room.select');
        Route::get('/perangkat', 'devices')->name('app.devices');
        Route::post('/perangkat/{device}/toggle', 'toggleDevice')->name('app.devices.toggle');
        Route::post('/perangkat/{device}/kecepatan', 'setSpeed')->name('app.devices.speed');
        Route::get('/insight', 'insight')->name('app.insight');
        Route::post('/insight/{key}/terapkan', 'applyInsight')->name('app.insight.apply');
        Route::post('/insight/{key}/umpan-balik', 'insightFeedback')->name('app.insight.feedback');
        Route::get('/profil', 'profile')->name('app.profile');
        Route::post('/profil/nama', 'updateName')->name('app.profile.name');
        Route::post('/profil/pengaturan', 'updateSetting')->name('app.profile.settings');
        Route::get('/riwayat', 'activity')->name('app.activity');
    });

    // -------- Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/pengguna', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/pengguna/{user}/peran', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::delete('/pengguna/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/ruangan', [AdminRoomController::class, 'index'])->name('rooms.index');
        Route::get('/ruangan/baru', [AdminRoomController::class, 'create'])->name('rooms.create');
        Route::post('/ruangan', [AdminRoomController::class, 'store'])->name('rooms.store');
        Route::get('/ruangan/{room}/ubah', [AdminRoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/ruangan/{room}', [AdminRoomController::class, 'update'])->name('rooms.update');
        Route::post('/ruangan/{room}/kunci', [AdminRoomController::class, 'regenerateKey'])->name('rooms.key');
        Route::delete('/ruangan/{room}', [AdminRoomController::class, 'destroy'])->name('rooms.destroy');

        Route::get('/perangkat', [AdminDeviceController::class, 'index'])->name('devices.index');
        Route::get('/perangkat/baru', [AdminDeviceController::class, 'create'])->name('devices.create');
        Route::post('/perangkat', [AdminDeviceController::class, 'store'])->name('devices.store');
        Route::get('/perangkat/{device}/ubah', [AdminDeviceController::class, 'edit'])->name('devices.edit');
        Route::put('/perangkat/{device}', [AdminDeviceController::class, 'update'])->name('devices.update');
        Route::post('/perangkat/{device}/toggle', [AdminDeviceController::class, 'toggle'])->name('devices.toggle');
        Route::delete('/perangkat/{device}', [AdminDeviceController::class, 'destroy'])->name('devices.destroy');
    });
});

// Login, daftar, logout, lupa password, dll. (dibuat oleh Laravel Breeze).
require __DIR__.'/auth.php';
