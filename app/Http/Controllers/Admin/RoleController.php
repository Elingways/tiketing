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
            ->with([
                'priorityRoutes.user:id,name,email',
                'head:id,name,email',
                'users:id,role_id,name,email,keahlian',
            ])
            ->withCount('users')
            ->orderByDesc('level')
            ->orderBy('name')
            ->get();

        $configuredRoles = $roles->filter(fn ($role) => $role->head_user_id || $role->priorityRoutes->isNotEmpty());

        return view('admin.roles.index', compact('roles', 'configuredRoles'));
    }
}
