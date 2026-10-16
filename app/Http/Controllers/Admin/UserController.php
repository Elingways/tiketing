<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role.parent', 'supervisor.role'])
            ->orderBy('role_id')
            ->orderBy('name')
            ->get();

        $roles = Role::orderBy('level')->orderBy('name')->get();

        return view('admin.users', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4'],
            'role_id' => ['required', 'exists:roles,id'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:120'],
        ]);

        $role = Role::find($validated['role_id']);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'supervisor_id' => $validated['supervisor_id'] ?: null,
            'identity_number' => $validated['identity_number'],
            'department' => $validated['department'],
            'avatar_color' => $role?->badge_color ?? 'indigo',
        ]);

        return back()->with('success', "Pengguna '{$validated['name']}' berhasil ditambahkan dengan role {$role?->name}!");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:120'],
        ]);

        $role = Role::find($validated['role_id']);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'supervisor_id' => $validated['supervisor_id'] ?: null,
            'identity_number' => $validated['identity_number'],
            'department' => $validated['department'],
            'avatar_color' => $role?->badge_color ?? 'indigo',
        ]);

        return back()->with('success', "Data & role pengguna '{$user->name}' berhasil diperbarui!");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan login.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Pengguna '{$name}' berhasil dihapus.");
    }
}
