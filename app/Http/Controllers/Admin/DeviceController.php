<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        return view('admin.devices.index', [
            'devices' => Device::with('room')->orderBy('room_id')->orderBy('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.devices.form', [
            'device' => new Device(['room_id' => $request->query('room'), 'type' => 'lamp', 'is_online' => true]),
            'rooms' => Room::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Device::create($this->validated($request));

        return redirect()->route('admin.devices.index')->with('status', 'Perangkat berhasil ditambahkan.');
    }

    public function edit(Device $device): View
    {
        return view('admin.devices.form', [
            'device' => $device,
            'rooms' => Room::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $device->update($this->validated($request));

        return redirect()->route('admin.devices.index')->with('status', 'Perangkat berhasil disimpan.');
    }

    public function toggle(Request $request, Device $device): RedirectResponse
    {
        $device->turn(! $device->is_on, $request->user());

        return back()->with('status', $device->name.' '.($device->is_on ? 'dinyalakan.' : 'dimatikan.'));
    }

    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();

        return redirect()->route('admin.devices.index')->with('status', 'Perangkat dihapus.');
    }

    private function validated(Request $request): array
    {
        $input = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:lamp,fan,other'],
        ]);

        // Mencentang "online" menandai perangkat online secara manual (riwayat terakhir terlihat dikosongkan);
        // perangkat sungguhan akan mengisinya lagi begitu melapor.
        return $input + ($request->boolean('is_online')
            ? ['is_online' => true, 'last_seen_at' => null]
            : ['is_online' => false]);
    }
}
