<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /** Perangkat yang sudah pernah melapor dianggap offline bila diam lebih lama dari ini. */
    public const OFFLINE_AFTER_MINUTES = 2;

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

    /**
     * Status online yang sebenarnya. "is_online" adalah saklar manual admin (false = dipaksa offline).
     * Perangkat yang pernah melapor (last_seen_at terisi) dianggap offline bila tidak melapor
     * selama OFFLINE_AFTER_MINUTES. Perangkat yang belum pernah melapor dianggap online
     * selama saklar manual menyala, supaya aplikasi bisa dicoba tanpa perangkat sungguhan.
     */
    public function isOnline(): bool
    {
        if (! $this->is_online) {
            return false;
        }

        return $this->last_seen_at === null
            || $this->last_seen_at->gt(now()->subMinutes(self::OFFLINE_AFTER_MINUTES));
    }

    /** Padanan isOnline() untuk query (dipakai untuk menghitung perangkat online). */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('is_online', true)->where(function (Builder $q) {
            $q->whereNull('last_seen_at')
                ->orWhere('last_seen_at', '>', now()->subMinutes(self::OFFLINE_AFTER_MINUTES));
        });
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
