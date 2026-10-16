<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HierarchyController extends Controller
{
    public function index()
    {
        $roles = Role::with(['parent', 'children', 'users', 'menus'])
            ->orderBy('is_superuser', 'desc')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        // Root roles untuk pohon visualisasi hirarki organisasi/tiketing
        $treeRoots = Role::with('childrenRecursive')
            ->whereNull('parent_id')
            ->where('is_superuser', false)
            ->get();

        return view('admin.hierarchy', compact('roles', 'treeRoots'));
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

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?: null,
            'badge_color' => $validated['badge_color'],
            'icon' => 'user-group',
            'description' => $validated['description'],
            'is_superuser' => false,
            'can_create_ticket' => $request->boolean('can_create_ticket', true),
            'can_receive_ticket' => $request->boolean('can_receive_ticket', true),
        ]);

        Role::recalculateLevels();

        // Berikan akses menu standar secara otomatis ke role baru (Beranda, Tiket Saya, Riwayat Tiket, + Inbox sesuai kapabilitas)
        $defaultSlugs = ['dashboard', 'tickets-my', 'tickets-history'];
        if ($role->can_receive_ticket) {
            $defaultSlugs[] = 'tickets-inbox';
        }
        $defaultMenuIds = Menu::whereIn('slug', $defaultSlugs)->pluck('id');
        $role->menus()->sync($defaultMenuIds);

        $parentName = $role->parent?->name ?? 'Puncak Hirarki (Root)';

        return back()->with('success', "Role baru '{$role->name}' berhasil dibuat dengan hirarki atasan menuju: {$parentName}!");
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
}
