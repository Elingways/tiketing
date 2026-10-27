<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::where('is_superuser', false)
            ->with([
                'division:id,name,badge_color',
                'parent:id,name',
                'head:id,name,email',
                'users:id,role_id,name,email,keahlian',
            ])
            ->withCount('users')
            ->orderByDesc('level')
            ->orderBy('name')
            ->get();

        $divisions = Division::with([
            'roles' => fn ($query) => $query->withCount('users')->with('head:id,name,email', 'users:id,role_id,name,email,keahlian'),
            'categories:id,division_id,name',
            'headRole.head:id,name,email',
            'spvRole.head:id,name,email',
            'staffRole',
            'members.user:id,name,email',
        ])->orderBy('name')->get();

        $people = User::query()
            ->whereHas('role', fn ($query) => $query->where('is_superuser', false)->where('slug', '!=', 'mahasiswa'))
            ->with('role:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        return view('admin.roles.index', compact('roles', 'divisions', 'people'));
    }
}
