<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'privacy_mode',
        'notifications',
    ];

    // Nilai awal di memori sama dengan default di database, supaya user yang baru dibuat
    // (misalnya lewat factory) langsung berperan "user" tanpa perlu dimuat ulang.
    protected $attributes = [
        'role' => 'user',
        'privacy_mode' => false,
        'notifications' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'privacy_mode' => 'boolean',
            'notifications' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr(trim($this->name), 0, 2));
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0] ?: 'pengguna';
    }
}
