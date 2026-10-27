<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Redirect to the first operational division's or role's user management page.
     */
    public function index()
    {
        if (Division::exists()) {
            $firstDivision = Division::first();
            return redirect()->route('admin.users.division', $firstDivision->slug);
        }

        $firstRole = Role::where('is_superuser', false)
            ->whereHas('users')
            ->first() ?? Role::where('is_superuser', false)->first();

        if ($firstRole) {
            return redirect()->route('admin.users.role', $firstRole->slug);
        }

        return redirect()->route('admin.roles');
    }

    /**
     * Display users table for a specific division with profile modal and password reset.
     */
    public function divisionUsers(Request $request, Division $division)
    {
        $search = trim((string) $request->input('search', ''));
        $statusFilter = trim((string) $request->input('status', ''));
        $roleFilter = trim((string) $request->input('role_id', ''));

        $allDivisions = Division::orderBy('name')->get();
        $divisionRoles = $division->roles()->orderBy('level')->orderBy('name')->get();
        if ($divisionRoles->isEmpty()) {
            $divisionRoles = collect([$division->staffRole, $division->spvRole, $division->headRole])->filter()->unique('id');
        }

        $divisionRoleIds = $divisionRoles->pluck('id')->all();

        $query = User::query()
            ->where(function ($q) use ($division, $divisionRoleIds) {
                if (!empty($divisionRoleIds)) {
                    $q->whereIn('role_id', $divisionRoleIds);
                } else {
                    $q->whereRaw('0 = 1');
                }
                $q->orWhereIn('id', function ($sub) use ($division) {
                    $sub->select('user_id')->from('division_user')->where('division_id', $division->id);
                });
            })
            ->with(['role'])
            ->withCount([
                'assignedTickets as active_tickets_count' => function ($q) {
                    $q->whereNotIn('status', ['resolved', 'rejected', 'closed']);
                },
                'assignedTickets as completed_tickets_count' => function ($q) {
                    $q->whereIn('status', ['resolved', 'closed']);
                },
                'createdTickets as created_tickets_count',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%")
                  ->orWhere('keahlian', 'like', "%{$search}%")
                  ->orWhere('identity_number', 'like', "%{$search}%");
            });
        }

        if ($statusFilter !== '') {
            $query->where('status_akademik', $statusFilter);
        }

        if ($roleFilter !== '') {
            $query->where('role_id', $roleFilter);
        }

        $users = $query->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        $totalUsers = (clone $query)->count();
        $totalActiveStaff = (clone $query)->where('status_akademik', 'Aktif')->count();

        $headMember = $division->members->firstWhere('position', 'head');
        $spvMember = $division->members->firstWhere('position', 'spv');
        $headUser = $headMember?->user ?? $division->headRole?->head;
        $spvUser = $spvMember?->user ?? $division->spvRole?->head;

        return view('admin.users.division', compact(
            'division',
            'allDivisions',
            'divisionRoles',
            'users',
            'headUser',
            'spvUser',
            'totalUsers',
            'totalActiveStaff',
            'search',
            'statusFilter',
            'roleFilter'
        ));
    }

    /**
     * Display users table for a specific role with profile modal and password reset.
     */
    public function roleUsers(Request $request, Role $role)
    {
        $search = trim((string) $request->input('search', ''));
        $statusFilter = trim((string) $request->input('status', ''));
        $positionFilter = trim((string) $request->input('position', ''));

        $allRoles = Role::where('is_superuser', false)
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $query = $role->users()
            ->with(['role'])
            ->withCount([
                'assignedTickets as active_tickets_count' => function ($q) {
                    $q->whereNotIn('status', ['resolved', 'rejected', 'closed']);
                },
                'assignedTickets as completed_tickets_count' => function ($q) {
                    $q->whereIn('status', ['resolved', 'closed']);
                },
                'createdTickets as created_tickets_count',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%")
                  ->orWhere('keahlian', 'like', "%{$search}%")
                  ->orWhere('identity_number', 'like', "%{$search}%");
            });
        }

        if ($statusFilter !== '') {
            $query->where('status_akademik', $statusFilter);
        }

        if ($positionFilter === 'head' && $role->head_user_id) {
            $query->where('id', $role->head_user_id);
        } elseif ($positionFilter === 'staff' && $role->head_user_id) {
            $query->where('id', '!=', $role->head_user_id);
        }

        $headId = $role->head_user_id ?? 0;
        $users = $query->orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END", [$headId])
            ->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        $role->load('head');
        $totalUsers = $role->users()->count();
        $totalActiveStaff = $role->users()->where('status_akademik', 'Aktif')->count();
        $headUser = $role->head;

        return view('admin.users.role', compact(
            'role',
            'allRoles',
            'users',
            'headUser',
            'totalUsers',
            'totalActiveStaff',
            'search',
            'statusFilter',
            'positionFilter'
        ));
    }

    /**
     * Backward-compatible route for employees.
     */
    public function employees()
    {
        return $this->index();
    }

    /**
     * Store a new user for the role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:4'],
            'role_id' => ['required', 'exists:roles,id'],
            'jabatan' => ['nullable', 'string', 'max:120'],
            'keahlian' => ['nullable', 'string', 'max:120'],
            'status_akademik' => ['nullable', 'string', 'max:40'],
            'is_role_head' => ['nullable', 'boolean'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $rawPassword = !empty($validated['password']) ? trim($validated['password']) : 'password';

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => $rawPassword,
            'role_id' => $role->id,
            'user_type' => 'admin',
            'status_akademik' => $validated['status_akademik'] ?? 'Aktif',
            'jabatan' => !empty($validated['jabatan']) ? trim($validated['jabatan']) : 'Staff ' . $role->name,
            'keahlian' => !empty($validated['keahlian']) ? trim($validated['keahlian']) : null,
            'avatar_color' => $role->badge_color ?: 'indigo',
        ]);

        if ($request->boolean('is_role_head')) {
            $role->update(['head_user_id' => $user->id]);
        }

        return back()->with('success', "Pengguna '{$user->name}' berhasil ditambahkan ke role {$role->name}! Password login: '{$rawPassword}'.");
    }

    /**
     * Update user data.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'jabatan' => ['nullable', 'string', 'max:120'],
            'keahlian' => ['nullable', 'string', 'max:120'],
            'status_akademik' => ['nullable', 'string', 'max:40'],
            'is_role_head' => ['nullable', 'boolean'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $user->update([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role_id' => $role->id,
            'jabatan' => !empty($validated['jabatan']) ? trim($validated['jabatan']) : $user->jabatan,
            'keahlian' => array_key_exists('keahlian', $validated)
                ? (filled($validated['keahlian']) ? trim((string) $validated['keahlian']) : null)
                : $user->keahlian,
            'status_akademik' => $validated['status_akademik'] ?? $user->status_akademik,
            'avatar_color' => $role->badge_color ?: $user->avatar_color,
        ]);

        if ($request->has('is_role_head')) {
            if ($request->boolean('is_role_head')) {
                $role->update(['head_user_id' => $user->id]);
            } elseif ((int) $role->head_user_id === (int) $user->id) {
                $role->update(['head_user_id' => null]);
            }
        }

        return back()->with('success', "Data pengguna '{$user->name}' berhasil diperbarui!");
    }

    /**
     * Reset password for user (custom password or default: password).
     */
    public function resetPassword(Request $request, User $user)
    {
        $customPassword = trim((string) $request->input('password', ''));
        $passwordToSet = $customPassword !== '' ? $customPassword : 'password';

        $user->update([
            'password' => $passwordToSet,
        ]);

        return back()->with('success', "Password untuk pengguna '{$user->name}' ({$user->email}) berhasil direset menjadi '{$passwordToSet}'! Akun langsung aktif dan dapat digunakan login.");
    }

    /**
     * Delete user from system.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan login.');
        }

        $name = $user->name;
        Role::where('head_user_id', $user->id)->update(['head_user_id' => null]);

        $user->delete();

        return back()->with('success', "Data pengguna '{$name}' berhasil dihapus.");
    }
}
