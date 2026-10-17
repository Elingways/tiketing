<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index()
    {
        // Urutkan menu induk terlebih dahulu lalu sub-menu di bawah induknya
        $allMenus = Menu::with(['roles', 'parent', 'children.roles'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $orderedMenus = collect();
        foreach ($allMenus->whereNull('parent_id') as $parent) {
            $orderedMenus->push($parent);
            foreach ($allMenus->where('parent_id', $parent->id)->sortBy('sort_order') as $child) {
                $orderedMenus->push($child);
            }
        }
        // Tambahkan menu yang parent_id-nya yatim bila ada
        foreach ($allMenus as $item) {
            if (!$orderedMenus->contains('id', $item->id)) {
                $orderedMenus->push($item);
            }
        }

        $menus = $orderedMenus;
        $parentMenus = $allMenus->whereNull('parent_id')->values();
        $roles = Role::orderBy('level')->orderBy('name')->get();

        return view('admin.menus', compact('menus', 'parentMenus', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'route_name' => ['required', 'string', 'max:180'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'group' => ['required', 'string', 'max:40'],
            'badge_text' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:250'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $parent = !empty($validated['parent_id']) ? Menu::find($validated['parent_id']) : null;
        $group = $parent ? $parent->group : $validated['group'];

        $slug = Str::slug($validated['name']);
        if (Menu::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(10, 99);
        }

        $menu = Menu::create([
            'parent_id' => $parent?->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'route_name' => trim($validated['route_name']),
            'icon' => ($validated['icon'] ?? null) ?: 'ticket',
            'color' => $validated['color'] ?? 'indigo',
            'group' => $group,
            'badge_text' => ($validated['badge_text'] ?? null) ?: null,
            'description' => ($validated['description'] ?? null) ?: null,
            'sort_order' => $validated['sort_order'] ?? (Menu::count() + 1),
            'show_in_sidebar' => $request->boolean('show_in_sidebar', true),
            'show_in_launcher' => $parent ? false : $request->boolean('show_in_launcher', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $menu->roles()->sync($validated['roles'] ?? []);

        return back()->with('success', "Menu '{$menu->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'route_name' => ['required', 'string', 'max:180'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'group' => ['required', 'string', 'max:40'],
            'badge_text' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:250'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $parentId = !empty($validated['parent_id']) && (int) $validated['parent_id'] !== (int) $menu->id
            ? (int) $validated['parent_id']
            : null;
        $parent = $parentId ? Menu::find($parentId) : null;
        $group = $parent ? $parent->group : $validated['group'];

        $menu->update([
            'parent_id' => $parent?->id,
            'name' => $validated['name'],
            'route_name' => trim($validated['route_name']),
            'icon' => ($validated['icon'] ?? null) ?: $menu->icon ?: 'ticket',
            'color' => $validated['color'] ?? $menu->color ?? 'indigo',
            'group' => $group,
            'badge_text' => ($validated['badge_text'] ?? null) ?: null,
            'description' => ($validated['description'] ?? null) ?: null,
            'sort_order' => $validated['sort_order'],
            'show_in_sidebar' => $request->boolean('show_in_sidebar'),
            'show_in_launcher' => $parent ? false : $request->boolean('show_in_launcher'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->has('roles')) {
            $menu->roles()->sync($validated['roles'] ?? []);
        }

        return back()->with('success', "Menu '{$menu->name}' berhasil diperbarui.");
    }

    /**
     * Simpan Matriks Hak Akses Menu <-> Role Sekaligus
     */
    public function syncMatrix(Request $request)
    {
        $matrix = $request->input('matrix', []);
        $menus = Menu::all();

        foreach ($menus as $menu) {
            $roleIds = $matrix[$menu->id] ?? [];
            $menu->roles()->sync($roleIds);
        }

        return back()->with('success', 'Hak akses menu untuk seluruh role berhasil disimpan.');
    }

    public function destroy(Menu $menu)
    {
        if (in_array($menu->slug, ['dashboard', 'admin-menus'], true)) {
            return back()->with('error', 'Menu inti sistem tidak dapat dihapus.');
        }

        // Jika menu induk dihapus, naikkan sub-menu di bawahnya menjadi menu mandiri
        Menu::where('parent_id', $menu->id)->update(['parent_id' => null]);

        $name = $menu->name;
        $menu->delete();

        return back()->with('success', "Menu '{$name}' berhasil dihapus.");
    }
}

