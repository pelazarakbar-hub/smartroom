<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Room extends Model
{
    protected $fillable = [
        'name',
        'description',
        'api_key',
        'auto_join',
        'auto_fan_above',
    ];

    // Kunci perangkat tidak ikut tampil kalau model diubah menjadi JSON.
    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'auto_join' => 'boolean',
            'auto_fan_above' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            if (empty($room->api_key)) {
                $room->api_key = Str::random(40);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Tandai semua perangkat di ruangan ini sebagai baru saja terlihat.
     * Dengan $onlyIfOlderThan, tulis ke database hanya bila data terakhir sudah lebih tua
     * dari sekian detik (perangkat yang mengecek status tiap beberapa detik tidak membebani database).
     */
    public function markDevicesSeen(int $onlyIfOlderThan = 0): void
    {
        $query = $this->devices();

        if ($onlyIfOlderThan > 0) {
            $query->where(function ($q) use ($onlyIfOlderThan) {
                $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subSeconds($onlyIfOlderThan));
            });
        }

        $query->update(['is_online' => true, 'last_seen_at' => now()]);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function latestReading(): ?SensorReading
    {
        return $this->readings()->orderByDesc('recorded_at')->first();
    }
}
