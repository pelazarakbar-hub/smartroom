<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        return view('admin.rooms.index', [
            'rooms' => Room::withCount(['users', 'devices'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.rooms.form', [
            'room' => new Room(),
            'users' => $this->users(),
            'selected' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $room = Room::create($data['room']);
        $room->users()->sync($data['users']);

        return redirect()->route('admin.rooms.edit', $room)->with('status', 'Ruangan berhasil dibuat.');
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.form', [
            'room' => $room,
            'users' => $this->users(),
            'selected' => $room->users()->pluck('users.id')->all(),
        ]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $this->validated($request);

        $room->update($data['room']);
        $room->users()->sync($data['users']);

        return redirect()->route('admin.rooms.edit', $room)->with('status', 'Ruangan berhasil disimpan.');
    }

    public function regenerateKey(Room $room): RedirectResponse
    {
        $room->update(['api_key' => Str::random(40)]);

        return back()->with('status', 'Kunci baru dibuat. Perbarui kunci di perangkat agar tetap terhubung.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $room->delete();

        return redirect()->route('admin.rooms.index')->with('status', 'Ruangan dihapus beserta perangkat dan datanya.');
    }

    private function users()
    {
        return User::where('role', 'user')->orderBy('name')->get();
    }

    private function validated(Request $request): array
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'auto_fan_above' => ['nullable', 'numeric', 'between:20,45'],
            'users' => ['nullable', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
        ]);

        return [
            'room' => [
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'auto_join' => $request->boolean('auto_join'),
                'auto_fan_above' => $input['auto_fan_above'] ?? null,
            ],
            'users' => $input['users'] ?? [],
        ];
    }
}
