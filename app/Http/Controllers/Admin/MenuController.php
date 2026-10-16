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
        $menus = Menu::with('roles')->orderBy('sort_order')->get();
        $roles = Role::orderBy('level')->orderBy('name')->get();

        return view('admin.menus', compact('menus', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'route_name' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'group' => ['required', 'in:main,ticketing,management'],
            'badge_text' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $slug = Str::slug($validated['name']);
        if (Menu::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(10, 99);
        }

        $menu = Menu::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'route_name' => $validated['route_name'],
            'icon' => $validated['icon'],
            'color' => $validated['color'],
            'group' => $validated['group'],
            'badge_text' => $validated['badge_text'],
            'description' => $validated['description'],
            'sort_order' => $validated['sort_order'],
            'show_in_sidebar' => $request->boolean('show_in_sidebar', true),
            'show_in_launcher' => $request->boolean('show_in_launcher', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $menu->roles()->sync($validated['roles'] ?? []);

        return back()->with('success', "Menu SuperApp '{$menu->name}' berhasil ditambahkan!");
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'route_name' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'group' => ['required', 'in:main,ticketing,management'],
            'badge_text' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $menu->update([
            'name' => $validated['name'],
            'route_name' => $validated['route_name'],
            'icon' => $validated['icon'],
            'color' => $validated['color'],
            'group' => $validated['group'],
            'badge_text' => $validated['badge_text'],
            'description' => $validated['description'],
            'sort_order' => $validated['sort_order'],
            'show_in_sidebar' => $request->boolean('show_in_sidebar'),
            'show_in_launcher' => $request->boolean('show_in_launcher'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $menu->roles()->sync($validated['roles'] ?? []);

        return back()->with('success', "Menu '{$menu->name}' dan hak akses rolenya berhasil diperbarui!");
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

        return back()->with('success', 'Matriks hak akses menu untuk seluruh role berhasil disimpan!');
    }

    public function destroy(Menu $menu)
    {
        $name = $menu->name;
        $menu->delete();

        return back()->with('success', "Menu '{$name}' berhasil dihapus.");
    }
}
