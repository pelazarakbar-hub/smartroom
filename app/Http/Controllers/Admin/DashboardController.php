<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $rooms = Room::withCount(['users', 'devices'])->orderBy('name')->get();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::where('role', 'user')->count(),
                'rooms' => $rooms->count(),
                'devices' => Device::count(),
                'online' => Device::online()->count(),
                'active' => Device::where('is_on', true)->count(),
            ],
            'rooms' => $rooms,
            'readings' => $rooms->mapWithKeys(fn (Room $room) => [$room->id => $room->latestReading()]),
            'logs' => DeviceLog::with(['device.room', 'user'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
