<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->decimal('temperature', 4, 1);
            $table->unsignedTinyInteger('humidity')->default(0);
            $table->unsignedSmallInteger('light')->default(0);       // lux
            $table->unsignedTinyInteger('occupants')->default(0);    // jumlah orang terdeteksi
            $table->unsignedSmallInteger('air_quality')->default(40); // indeks AQI
            $table->timestamp('recorded_at');
            $table->index(['room_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
