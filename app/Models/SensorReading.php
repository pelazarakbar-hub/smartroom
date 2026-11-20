<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'temperature',
        'humidity',
        'light',
        'occupants',
        'air_quality',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'humidity' => 'integer',
            'light' => 'integer',
            'occupants' => 'integer',
            'air_quality' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
