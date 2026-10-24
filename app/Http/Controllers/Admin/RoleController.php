<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::where('is_superuser', false)
            ->with(['priorityRoutes.user:id,name', 'head:id,name'])
            ->withCount('users')
            ->orderByDesc('level')
            ->orderBy('name')
            ->get();

        $roles->whereNotNull('head_user_id')->load(['users:id,role_id,name,keahlian']);

        $configuredRoles = $roles->filter(fn ($role) => $role->head_user_id || $role->priorityRoutes->isNotEmpty());

        return view('admin.roles.index', compact('roles', 'configuredRoles'));
    }
}
