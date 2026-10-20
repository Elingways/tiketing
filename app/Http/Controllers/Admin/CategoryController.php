<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = TicketCategory::with(['initialTargetRole.parent', 'maxApprovalRole'])
            ->withCount('tickets')
            ->latest()
            ->get();

        $roles = Role::where('is_superuser', false)
            ->with(['priorityRoutes.user:id,name', 'head:id,name'])
            ->withCount('users')
            ->orderByDesc('level')
            ->orderBy('name')
            ->get();

        $roles->whereNotNull('head_user_id')->load(['users:id,role_id,name,keahlian']);

        return view('admin.categories', compact('categories', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'description' => ['nullable', 'string', 'max:500'],
            'workflow_type' => ['required', 'in:escalation,tiered_approval'],
            'workflow_roles' => ['nullable', 'array'],
            'workflow_roles.*' => ['nullable', 'exists:roles,id'],
            'initial_target_role_id' => ['nullable', 'exists:roles,id'],
            'max_approval_role_id' => ['nullable', 'exists:roles,id'],
            'template_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip', 'max:10240'],
        ]);

        $workflowRoles = collect($validated['workflow_roles'] ?? [])
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $initialTargetRoleId = $validated['initial_target_role_id'] ?? null;
        $maxApprovalRoleId = $validated['max_approval_role_id'] ?? null;

        if (!empty($workflowRoles)) {
            $rolesById = Role::whereIn('id', $workflowRoles)->get()->keyBy('id');
            $orderedRoles = collect($workflowRoles)->map(fn ($id) => $rolesById->get($id))->filter()->values();
            $recipientRoles = ($orderedRoles->count() > 1 && !$orderedRoles->first()->can_receive_ticket)
                ? $orderedRoles->slice(1)->values()
                : $orderedRoles;

            $initialTargetRoleId = $recipientRoles->first()?->id ?? $initialTargetRoleId;
            $maxApprovalRoleId = $recipientRoles->last()?->id ?? $maxApprovalRoleId;
        }

        $templatePath = null;
        $templateName = null;
        if ($request->hasFile('template_file')) {
            $file = $request->file('template_file');
            $templateName = $file->getClientOriginalName();
            $templatePath = $file->store('category-templates', 'public');
        }

        $slug = Str::slug($validated['name']);
        if (TicketCategory::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(10, 99);
        }

        TicketCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon' => 'ticket',
            'color' => $validated['color'] ?? 'indigo',
            'description' => $validated['description'] ?? null,
            'workflow_roles' => !empty($workflowRoles) ? $workflowRoles : null,
            'initial_target_role_id' => $initialTargetRoleId ?: null,
            'workflow_type' => $validated['workflow_type'],
            'max_approval_role_id' => $maxApprovalRoleId ?: null,
            'template_path' => $templatePath,
            'template_name' => $templateName,
            'is_attachment_required' => $request->has('is_attachment_required')
                ? $request->boolean('is_attachment_required')
                : ($templatePath !== null),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Kategori layanan '{$validated['name']}' beserta tahapan hirarki dan template dokumennya berhasil ditambahkan!");
    }

    public function update(Request $request, TicketCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'in:indigo,emerald,amber,rose,violet,sky,teal,fuchsia'],
            'description' => ['nullable', 'string', 'max:500'],
            'workflow_type' => ['required', 'in:escalation,tiered_approval'],
            'workflow_roles' => ['nullable', 'array'],
            'workflow_roles.*' => ['nullable', 'exists:roles,id'],
            'initial_target_role_id' => ['nullable', 'exists:roles,id'],
            'max_approval_role_id' => ['nullable', 'exists:roles,id'],
            'template_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip', 'max:10240'],
        ]);

        $workflowRoles = collect($validated['workflow_roles'] ?? [])
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $initialTargetRoleId = $validated['initial_target_role_id'] ?? $category->initial_target_role_id;
        $maxApprovalRoleId = $validated['max_approval_role_id'] ?? $category->max_approval_role_id;

        if (!empty($workflowRoles)) {
            $rolesById = Role::whereIn('id', $workflowRoles)->get()->keyBy('id');
            $orderedRoles = collect($workflowRoles)->map(fn ($id) => $rolesById->get($id))->filter()->values();
            $recipientRoles = ($orderedRoles->count() > 1 && !$orderedRoles->first()->can_receive_ticket)
                ? $orderedRoles->slice(1)->values()
                : $orderedRoles;

            $initialTargetRoleId = $recipientRoles->first()?->id;
            $maxApprovalRoleId = $recipientRoles->last()?->id;
        }

        $templatePath = $category->template_path;
        $templateName = $category->template_name;

        if ($request->boolean('remove_template') && $templatePath) {
            if (Storage::disk('public')->exists($templatePath)) {
                Storage::disk('public')->delete($templatePath);
            }
            $templatePath = null;
            $templateName = null;
        }

        if ($request->hasFile('template_file')) {
            if ($templatePath && Storage::disk('public')->exists($templatePath)) {
                Storage::disk('public')->delete($templatePath);
            }
            $file = $request->file('template_file');
            $templateName = $file->getClientOriginalName();
            $templatePath = $file->store('category-templates', 'public');
        }

        $category->update([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? $category->color ?? 'indigo',
            'description' => $validated['description'] ?? null,
            'workflow_roles' => !empty($workflowRoles) ? $workflowRoles : null,
            'initial_target_role_id' => $initialTargetRoleId ?: null,
            'workflow_type' => $validated['workflow_type'],
            'max_approval_role_id' => $maxApprovalRoleId ?: null,
            'template_path' => $templatePath,
            'template_name' => $templateName,
            'is_attachment_required' => $request->boolean('is_attachment_required'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Kategori layanan '{$category->name}' berhasil diperbarui!");
    }

    public function destroy(TicketCategory $category)
    {
        if ($category->tickets()->exists()) {
            return back()->with('error', 'Kategori layanan ini memiliki tiket aktif dan tidak dapat dihapus.');
        }

        if ($category->template_path && Storage::disk('public')->exists($category->template_path)) {
            Storage::disk('public')->delete($category->template_path);
        }

        $name = $category->name;
        $category->delete();

        return back()->with('success', "Kategori layanan '{$name}' berhasil dihapus.");
    }
}

