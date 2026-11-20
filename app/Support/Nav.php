<?php

namespace App\Support;

use App\Models\User;

class Nav
{
    /**
     * Menu navigasi sesuai peran. 'match' dipakai untuk menandai menu yang aktif.
     */
    public static function items(?User $user): array
    {
        if ($user && $user->isAdmin()) {
            return [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'match' => ['admin.dashboard']],
                ['route' => 'admin.users.index', 'label' => 'Pengguna', 'icon' => 'users', 'match' => ['admin.users.*']],
                ['route' => 'admin.rooms.index', 'label' => 'Ruangan', 'icon' => 'room', 'match' => ['admin.rooms.*']],
                ['route' => 'admin.devices.index', 'label' => 'Perangkat', 'icon' => 'device', 'match' => ['admin.devices.*']],
            ];
        }

        return [
            ['route' => 'app.home', 'label' => 'Beranda', 'icon' => 'home', 'match' => ['app.home']],
            ['route' => 'app.room', 'label' => 'Ruangan', 'icon' => 'room', 'match' => ['app.room']],
            ['route' => 'app.devices', 'label' => 'Perangkat', 'icon' => 'device', 'match' => ['app.devices']],
            ['route' => 'app.insight', 'label' => 'AI Insight', 'icon' => 'spark', 'match' => ['app.insight']],
            ['route' => 'app.profile', 'label' => 'Profil', 'icon' => 'user', 'match' => ['app.profile', 'app.activity', 'profile.edit']],
        ];
    }
}
