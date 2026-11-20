<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    public const TYPES = [
        'lamp' => 'Lampu',
        'fan' => 'Kipas',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'room_id',
        'name',
        'type',
        'is_on',
        'level',
        'is_online',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_on' => 'boolean',
            'is_online' => 'boolean',
            'level' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeviceLog::class);
    }

    public function icon(): string
    {
        return match ($this->type) {
            'lamp' => 'bulb',
            'fan' => 'fan',
            default => 'device',
        };
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Lainnya';
    }

    /**
     * Ubah status nyala/mati dan catat ke riwayat aktivitas.
     * Perangkat sungguhan membaca status ini lewat API (GET /api/device/state).
     */
    public function turn(bool $on, ?User $by = null, bool $auto = false): void
    {
        if ($this->is_on === $on) {
            return;
        }

        $this->update(['is_on' => $on]);
        $this->log($this->name.' '.($on ? 'dinyalakan' : 'dimatikan').($auto ? ' otomatis' : ''), $by);
    }

    public function setLevel(int $level, ?User $by = null): void
    {
        $level = max(1, min(3, $level));

        if ($this->level === $level) {
            return;
        }

        $this->update(['level' => $level]);
        $this->log("Kecepatan {$this->name} diatur ke level {$level}", $by);
    }

    private function log(string $message, ?User $by): void
    {
        DeviceLog::create([
            'device_id' => $this->id,
            'user_id' => $by?->id,
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}
