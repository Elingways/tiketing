<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RolePriorityRoute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HierarchyController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.categories');
    }

    /**
     * Buat Role Baru & Tentukan Hirarki Atasannya (parent_id)
     * Contoh: Superuser membuat role "Asisten Dosen" -> parent_id diarahkan ke "Dosen", atau "Dosen" -> ke "Kaprodi"
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'exists:roles,id'],
            'badge_color' => ['required', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'description' => ['nullable', 'string', 'max:300'],
            'can_create_ticket' => ['nullable', 'boolean'],
            'can_receive_ticket' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        if (Role::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(10, 99);
        }

        $staffRows = $this->filledStaffRows($request->input('staff', []));
        $this->validateStaffRows($staffRows);
        $this->assertRouteIndexes($request->input('routes', []), $staffRows);

        $role = DB::transaction(function () use ($validated, $request, $slug, $staffRows) {
            $role = Role::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'parent_id' => ($validated['parent_id'] ?? null) ?: null,
                'badge_color' => $validated['badge_color'],
                'icon' => 'user-group',
                'description' => $validated['description'],
                'is_superuser' => false,
                'can_create_ticket' => $request->boolean('can_create_ticket', true),
                'can_receive_ticket' => $request->boolean('can_receive_ticket', true),
            ]);

            $created = $this->createStaffUsers($role, $staffRows);
            $this->storeRoutesByStaffIndex($role, $request->input('routes', []), $created);
            $this->assignHead($role, $request->input('head_index'), $created);

            return $role;
        });

        Role::recalculateLevels();

        // Berikan akses menu standar secara otomatis ke role baru (Beranda, Tiket Saya, Riwayat Tiket, + Inbox sesuai kapabilitas)
        $defaultSlugs = ['dashboard', 'tickets-my', 'tickets-history'];
        if ($role->can_receive_ticket) {
            $defaultSlugs[] = 'tickets-inbox';
        }
        $defaultMenuIds = Menu::whereIn('slug', $defaultSlugs)->pluck('id');
        $role->menus()->sync($defaultMenuIds);

        $parentName = $role->parent?->name ?? 'Puncak Hirarki (Root)';
        $staffCount = $role->users()->count();
        $routeCount = $role->priorityRoutes()->count();
        $staffNote = $staffCount > 0 ? " {$staffCount} staff ditambahkan." : '';
        $routeNote = $routeCount > 0 ? " {$routeCount} nomor prioritas sudah diarahkan ke staff." : '';
        $passwordNote = $staffCount > 0 ? ' Password awal staff: password.' : '';

        return back()->with('success', "Role baru '{$role->name}' berhasil dibuat (atasan: {$parentName}).{$staffNote}{$routeNote}{$passwordNote}");
    }

    public function roleStaff(Role $role)
    {
        $role->load([
            'users' => fn ($query) => $query->orderBy('name')->limit(200),
            'priorityRoutes',
        ]);

        $routes = [];
        foreach (range(1, 8) as $level) {
            $routes[$level] = (string) ($role->priorityRoutes->firstWhere('priority_level', $level)?->user_id ?? '');
        }

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'actionUrl' => route('admin.hierarchy.staff.sync', $role),
            'staff' => $role->users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'keahlian' => $user->keahlian,
            ])->values(),
            'routes' => $routes,
            'head_user_id' => $role->head_user_id ? (string) $role->head_user_id : '',
            'truncated' => $role->users()->count() > 200,
        ]);
    }

    public function syncStaff(Request $request, Role $role)
    {
        if ($role->is_superuser) {
            return back()->with('error', 'Role Superuser tidak diatur lewat form staff.');
        }

        $staffRows = $this->filledStaffRows($request->input('staff', []));
        $this->validateStaffRows($staffRows);

        DB::transaction(function () use ($request, $role, $staffRows) {
            $created = $this->createStaffUsers($role, $staffRows);
            $this->replaceRoutes($role, $request->input('routes', []), $created);
            if ($request->filled('head_user_id')) {
                $this->assignHead($role, $request->input('head_user_id'), $created);
            }
        });

        return back()->with('success', "Staff dan urutan prioritas role '{$role->name}' berhasil disimpan. Password awal staff baru: password.");
    }

    /**
     * Update Hirarki (Parent Role) & Konfigurasi Role Secara Dinamis
     */
    public function updateHierarchy(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'exists:roles,id'],
            'badge_color' => ['required', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'description' => ['nullable', 'string', 'max:300'],
            'can_create_ticket' => ['nullable', 'boolean'],
            'can_receive_ticket' => ['nullable', 'boolean'],
        ]);

        $newParentId = $validated['parent_id'] ? (int) $validated['parent_id'] : null;

        if ($role->wouldCreateCycle($newParentId)) {
            return back()->with('error', "Gagal memperbarui hirarki: Menjadikan role tersebut sebagai atasan dari '{$role->name}' akan menimbulkan perulangan (circular loop)!");
        }

        $role->update([
            'name' => $validated['name'],
            'parent_id' => $newParentId,
            'badge_color' => $validated['badge_color'],
            'description' => $validated['description'],
            'can_create_ticket' => $request->boolean('can_create_ticket'),
            'can_receive_ticket' => $request->boolean('can_receive_ticket'),
        ]);

        Role::recalculateLevels();

        $parentName = $role->fresh()->parent?->name ?? 'Puncak Hirarki (Root)';

        return back()->with('success', "Hirarki & pengaturan Role '{$role->name}' berhasil diperbarui (Atasan: {$parentName}).");
    }

    public function destroyRole(Role $role)
    {
        if ($role->is_superuser) {
            return back()->with('error', 'Role Superuser tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', "Role '{$role->name}' masih digunakan oleh pengguna aktif. Pindahkan role pengguna terlebih dahulu.");
        }

        // Sambungkan anak-anak dari role ini ke parent di atasnya agar rantai hirarki tidak terputus
        Role::where('parent_id', $role->id)->update(['parent_id' => $role->parent_id]);

        $name = $role->name;
        $role->delete();
        Role::recalculateLevels();

        return back()->with('success', "Role '{$name}' berhasil dihapus dan rantai hirarki telah disesuaikan.");
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $staff
     * @return array<int, array{name: string, email: string}>
     */
    private function filledStaffRows(array $staff): array
    {
        $rows = [];
        foreach ($staff as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($name === '' && $email === '') {
                continue;
            }
            $rows[(int) $index] = [
                'name' => $name,
                'email' => $email,
                'keahlian' => trim((string) ($row['keahlian'] ?? '')),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{name: string, email: string}>  $staffRows
     */
    private function validateStaffRows(array $staffRows): void
    {
        if ($staffRows === []) {
            return;
        }

        $emails = collect($staffRows)->pluck('email');
        if ($emails->count() !== $emails->unique()->count()) {
            throw ValidationException::withMessages([
                'staff' => 'Email staff tidak boleh sama.',
            ]);
        }

        validator(['staff' => $staffRows], [
            'staff' => ['array', 'max:20'],
            'staff.*.name' => ['required', 'string', 'max:150'],
            'staff.*.email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'staff.*.keahlian' => ['nullable', 'string', 'max:120'],
        ], [
            'staff.*.name.required' => 'Nama staff wajib diisi.',
            'staff.*.email.required' => 'Email staff wajib diisi.',
            'staff.*.email.unique' => 'Email staff sudah terdaftar.',
        ])->validate();
    }

    /**
     * @param  array<int|string, mixed>  $routes
     * @param  array<int, array{name: string, email: string}>  $staffRows
     */
    private function assertRouteIndexes(array $routes, array $staffRows): void
    {
        foreach ($routes as $level => $index) {
            if ($index === null || $index === '') {
                continue;
            }
            $level = (int) $level;
            if ($level < 1 || $level > 8 || !is_numeric($index) || !isset($staffRows[(int) $index])) {
                throw ValidationException::withMessages([
                    'routes' => 'Setiap nomor prioritas (1–8) harus diarahkan ke staff yang namanya sudah diisi.',
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{name: string, email: string}>  $staffRows
     * @return array<int, User>
     */
    private function createStaffUsers(Role $role, array $staffRows): array
    {
        $created = [];
        foreach ($staffRows as $index => $row) {
            $created[$index] = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => 'password',
                'role_id' => $role->id,
                'user_type' => 'admin',
                'status_akademik' => 'Aktif',
                'jabatan' => 'Staff ' . $role->name,
                'keahlian' => $row['keahlian'] !== '' ? $row['keahlian'] : null,
                'department' => $role->name,
                'avatar_color' => $role->badge_color ?: 'indigo',
            ]);
        }

        return $created;
    }

    /**
     * @param  array<int, User>  $created
     */
    private function assignHead(Role $role, mixed $target, array $created): void
    {
        if ($target === null || $target === '') {
            if (!$role->head_user_id && $created !== []) {
                $target = array_key_first($created);
            } else {
                return;
            }
        }

        $user = null;
        if (is_string($target) && str_starts_with($target, 'new-')) {
            $user = $created[(int) substr($target, 4)] ?? null;
        } elseif (is_numeric($target) && isset($created[(int) $target]) && !User::whereKey((int) $target)->where('role_id', $role->id)->exists()) {
            $user = $created[(int) $target];
        } elseif (is_numeric($target)) {
            $user = User::whereKey((int) $target)->where('role_id', $role->id)->first();
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'head_user_id' => 'Kepala harus salah satu staff role ini.',
            ]);
        }

        $user->update(['jabatan' => 'Kepala ' . $role->name]);
        $role->update(['head_user_id' => $user->id]);
    }

    /**
     * @param  array<int|string, mixed>  $routes
     * @param  array<int, User>  $created
     */
    private function storeRoutesByStaffIndex(Role $role, array $routes, array $created): void
    {
        foreach ($routes as $level => $index) {
            if ($index === null || $index === '' || !isset($created[(int) $index])) {
                continue;
            }
            $level = (int) $level;
            if ($level < 1 || $level > 8) {
                continue;
            }
            RolePriorityRoute::create([
                'role_id' => $role->id,
                'priority_level' => $level,
                'user_id' => $created[(int) $index]->id,
            ]);
        }
    }

    /**
     * @param  array<int|string, mixed>  $routes
     * @param  array<int, User>  $created
     */
    private function replaceRoutes(Role $role, array $routes, array $created): void
    {
        $allowedIds = $role->users()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rows = [];

        foreach ($routes as $level => $target) {
            if ($target === null || $target === '') {
                continue;
            }
            $level = (int) $level;
            if ($level < 1 || $level > 8) {
                throw ValidationException::withMessages([
                    'routes' => 'Nomor prioritas harus antara 1 dan 8.',
                ]);
            }

            $userId = null;
            if (is_string($target) && str_starts_with($target, 'new-')) {
                $index = (int) substr($target, 4);
                $userId = isset($created[$index]) ? (int) $created[$index]->id : null;
            } elseif (is_numeric($target)) {
                $userId = (int) $target;
            }

            if (!$userId || !in_array($userId, $allowedIds, true)) {
                throw ValidationException::withMessages([
                    'routes' => "Prioritas nomor {$level} harus diarahkan ke staff role ini.",
                ]);
            }

            $rows[$level] = $userId;
        }

        $role->priorityRoutes()->delete();
        foreach ($rows as $level => $userId) {
            RolePriorityRoute::create([
                'role_id' => $role->id,
                'priority_level' => $level,
                'user_id' => $userId,
            ]);
        }
    }
}
