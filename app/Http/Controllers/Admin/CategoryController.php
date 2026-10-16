<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = TicketCategory::with(['initialTargetRole.parent', 'maxApprovalRole', 'tickets'])
            ->latest()
            ->get();

        $roles = Role::where('is_superuser', false)->orderBy('level')->orderBy('name')->get();

        return view('admin.categories', compact('categories', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['required', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'description' => ['nullable', 'string', 'max:350'],
            'initial_target_role_id' => ['nullable', 'exists:roles,id'],
            'workflow_type' => ['required', 'in:escalation,tiered_approval'],
            'max_approval_role_id' => ['nullable', 'exists:roles,id'],
        ]);

        $slug = Str::slug($validated['name']);
        if (TicketCategory::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(10, 99);
        }

        TicketCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon' => 'ticket',
            'color' => $validated['color'],
            'description' => $validated['description'],
            'initial_target_role_id' => $validated['initial_target_role_id'] ?: null,
            'workflow_type' => $validated['workflow_type'],
            'max_approval_role_id' => $validated['max_approval_role_id'] ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Kategori tiket '{$validated['name']}' beserta konfigurasi alur hirarkinya berhasil ditambahkan!");
    }

    public function update(Request $request, TicketCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['required', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'description' => ['nullable', 'string', 'max:350'],
            'initial_target_role_id' => ['nullable', 'exists:roles,id'],
            'workflow_type' => ['required', 'in:escalation,tiered_approval'],
            'max_approval_role_id' => ['nullable', 'exists:roles,id'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'color' => $validated['color'],
            'description' => $validated['description'],
            'initial_target_role_id' => $validated['initial_target_role_id'] ?: null,
            'workflow_type' => $validated['workflow_type'],
            'max_approval_role_id' => $validated['max_approval_role_id'] ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', "Konfigurasi alur hirarki untuk kategori '{$category->name}' berhasil diperbarui!");
    }

    public function destroy(TicketCategory $category)
    {
        if ($category->tickets()->exists()) {
            return back()->with('error', 'Kategori ini memiliki tiket aktif dan tidak dapat dihapus.');
        }

        $name = $category->name;
        $category->delete();

        return back()->with('success', "Kategori '{$name}' berhasil dihapus.");
    }
}
