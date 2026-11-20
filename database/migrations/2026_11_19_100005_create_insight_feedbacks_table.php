<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insight_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('insight_key', 40);
            $table->boolean('helpful')->nullable();
            $table->boolean('applied')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'room_id', 'insight_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insight_feedbacks');
    }
};
