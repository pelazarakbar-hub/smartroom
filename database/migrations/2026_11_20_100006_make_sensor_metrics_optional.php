<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hanya suhu yang wajib. Kelembapan, cahaya, keberadaan, dan kualitas udara bergantung pada
 * sensor yang benar-benar terpasang, jadi kosong (NULL) berarti "tidak ada sensornya",
 * bukan nol atau angka karangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->unsignedTinyInteger('humidity')->nullable()->default(null)->change();
            $table->unsignedSmallInteger('light')->nullable()->default(null)->change();
            $table->unsignedTinyInteger('occupants')->nullable()->default(null)->change();
            $table->unsignedSmallInteger('air_quality')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('sensor_readings')->whereNull('humidity')->update(['humidity' => 0]);
        DB::table('sensor_readings')->whereNull('light')->update(['light' => 0]);
        DB::table('sensor_readings')->whereNull('occupants')->update(['occupants' => 0]);
        DB::table('sensor_readings')->whereNull('air_quality')->update(['air_quality' => 40]);

        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->unsignedTinyInteger('humidity')->default(0)->nullable(false)->change();
            $table->unsignedSmallInteger('light')->default(0)->nullable(false)->change();
            $table->unsignedTinyInteger('occupants')->default(0)->nullable(false)->change();
            $table->unsignedSmallInteger('air_quality')->default(40)->nullable(false)->change();
        });
    }
};
