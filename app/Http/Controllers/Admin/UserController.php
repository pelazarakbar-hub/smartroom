<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::withCount('rooms')->orderBy('role')->orderBy('name')->get(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', 'in:user,admin']]);

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa mengubah peran akun sendiri.');
        }

        $user->update(['role' => $data['role']]);

        return back()->with('status', "Peran {$user->name} diubah menjadi {$data['role']}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('status', "Akun {$user->name} dihapus.");
    }
}
