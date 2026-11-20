<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();
            // Kunci yang dipakai perangkat (ESP32 dsb.) untuk mengirim data ke ruangan ini.
            $table->string('api_key', 64)->unique();
            // Pengguna baru otomatis diberi akses ke ruangan ini (berguna untuk demo).
            $table->boolean('auto_join')->default(false);
            // Aturan otomatis: nyalakan kipas bila suhu >= nilai ini. Null = tidak aktif.
            $table->decimal('auto_fan_above', 4, 1)->nullable();
            $table->timestamps();
        });

        Schema::create('room_user', function (Blueprint $table) {
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_user');
        Schema::dropIfExists('rooms');
    }
};
