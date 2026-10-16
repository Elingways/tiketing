<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    /**
     * Daftar Tiket Masuk ke Role User Saat Ini + Search & Filter Kategori + Set Prioritas
     */
    public function inbox(Request $request)
    {
        $user = Auth::user()->load(['role.parent', 'role.children']);
        $scope = $request->query('scope', 'current_role');
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $statusFilter = $request->query('status');

        $query = Ticket::with(['creator', 'creatorRole', 'currentRole', 'category', 'assignedUser']);

        if ($scope === 'all_system' && $user->isSuperuser()) {
            // Superuser melihat seluruh tiket
        } elseif ($scope === 'history_role') {
            $query->whereHas('logs', function ($q) use ($user) {
                $q->where('from_role_id', $user->role_id);
            });
        } else {
            $query->where('current_role_id', $user->role_id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $tickets = $query->latest()->get();
        $categories = TicketCategory::orderBy('name')->get();

        return view('tickets.inbox', compact(
            'tickets',
            'user',
            'scope',
            'search',
            'categoryId',
            'statusFilter',
            'categories'
        ));
    }

    /**
     * Menu Riwayat Tiket Saya (Filter: Sudah Di-Approve vs Semua Riwayat + Aksi Preview Dokumen)
     */
    public function myTickets(Request $request)
    {
        $user = Auth::user()->load('role.parent');
        $tab = $request->query('tab', 'approved'); // Default tampilkan yang sudah di-approve sesuai permintaan user
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');

        $baseQuery = Ticket::with(['category', 'currentRole', 'creatorRole', 'assignedUser', 'logs'])
            ->where('creator_id', $user->id);

        $approvedCount = (clone $baseQuery)->where(function ($q) {
            $q->where('status', 'resolved')
                ->orWhereHas('logs', function ($lq) {
                    $lq->whereIn('action', ['approved_step', 'final_approved', 'resolved']);
                });
        })->count();

        $allCount = (clone $baseQuery)->count();

        $query = clone $baseQuery;

        if ($tab === 'approved') {
            // Menampilkan tiket yang sudah di-approve (baik disetujui bertahap maupun selesai di-approve final)
            $query->where(function ($q) {
                $q->where('status', 'resolved')
                    ->orWhereHas('logs', function ($lq) {
                        $lq->whereIn('action', ['approved_step', 'final_approved', 'resolved']);
                    });
            });
        } elseif ($tab === 'active') {
            $query->whereNotIn('status', ['resolved', 'rejected', 'closed']);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $tickets = $query->latest()->get();
        $categories = TicketCategory::orderBy('name')->get();

        return view('tickets.my', compact(
            'tickets',
            'user',
            'tab',
            'search',
            'categoryId',
            'categories',
            'approvedCount',
            'allCount'
        ));
    }

    /**
     * Halaman Buat Tiket (Fallback bila diakses via URL langsung, meskipun di UI utama sudah menggunakan Modal)
     */
    public function create()
    {
        $user = Auth::user()->load('role.parent');
        $categories = TicketCategory::with(['initialTargetRole.parent', 'maxApprovalRole'])
            ->where('is_active', true)
            ->get();

        $categoryChains = [];
        foreach ($categories as $cat) {
            $chain = $cat->getWorkflowChain($user->role);
            $categoryChains[$cat->id] = [
                'workflow_type' => $cat->workflow_type,
                'workflow_label' => $cat->workflow_type === 'tiered_approval'
                    ? 'Approval Berjenjang'
                    : 'Eskalasi / Disposisi',
                'target_role' => $chain->first()?->name ?? ($user->role?->parent?->name ?? 'Admin'),
                'chain_names' => array_merge(
                    [$user->role?->name ?? 'Pemohon'],
                    $chain->pluck('name')->toArray()
                ),
            ];
        }

        return view('tickets.create', compact('user', 'categories', 'categoryChains'));
    }

    /**
     * Simpan Tiket Baru dari Modal Buat Tiket
     */
    public function store(Request $request)
    {
        $user = Auth::user()->load('role.parent');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category_id' => ['required', 'exists:ticket_categories,id'],
            'description' => ['required', 'string', 'min:5'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip', 'max:10240'],
        ]);

        $category = TicketCategory::with('initialTargetRole')->findOrFail($validated['category_id']);

        $targetRole = $category->resolveTargetRoleFor($user->role)
            ?? Role::where('is_superuser', true)->first()
            ?? $user->role;

        $assignedUser = null;
        if ($user->supervisor && (int) $user->supervisor->role_id === (int) $targetRole->id) {
            $assignedUser = $user->supervisor;
        } else {
            $assignedUser = User::where('role_id', $targetRole->id)->first();
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('attachments', 'public');
        }

        $nextSequence = Ticket::count() + 1;
        $ticketNumber = sprintf('TKT-%s-%04d', now()->format('Ym'), $nextSequence);

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'category_id' => $category->id,
            'priority' => null,
            'workflow_type' => $category->workflow_type,
            'creator_id' => $user->id,
            'creator_role_id' => $user->role_id,
            'current_role_id' => $targetRole->id,
            'assigned_user_id' => $assignedUser?->id,
            'status' => 'open',
            'approval_step' => 1,
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $targetRole->id,
            'action' => 'created',
            'note' => "Tiket diajukan oleh {$user->name} ({$user->role?->name}) ke Role {$targetRole->name}."
                . ($attachmentName ? " Lampiran: {$attachmentName}" : ''),
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket #{$ticket->ticket_number} berhasil diajukan ke Role {$targetRole->name}.");
    }

    /**
     * Preview Dokumen Lampiran Secara Inline (Untuk Modal Preview / Tab Baru)
     */
    public function previewAttachment(Ticket $ticket)
    {
        if (!$ticket->attachment_path || !Storage::disk('public')->exists($ticket->attachment_path)) {
            abort(404, 'Dokumen lampiran tidak ditemukan.');
        }

        $fullPath = Storage::disk('public')->path($ticket->attachment_path);
        $mimeType = Storage::disk('public')->mimeType($ticket->attachment_path) ?? 'application/octet-stream';
        $filename = $ticket->attachment_name ?? basename($ticket->attachment_path);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    /**
     * Unduh Dokumen Lampiran Tiket
     */
    public function downloadAttachment(Ticket $ticket)
    {
        if (!$ticket->attachment_path || !Storage::disk('public')->exists($ticket->attachment_path)) {
            abort(404, 'Dokumen lampiran tidak ditemukan.');
        }

        return Storage::disk('public')->download(
            $ticket->attachment_path,
            $ticket->attachment_name ?? basename($ticket->attachment_path)
        );
    }

    /**
     * Set Prioritas Tiket oleh Jenjang Penerima
     */
    public function updatePriority(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');

        if (!$ticket->canBeHandledBy($user)) {
            return back()->with('error', 'Hanya role penanggung jawab saat ini yang dapat mengatur prioritas tiket.');
        }

        $validated = $request->validate([
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);

        $ticket->update([
            'priority' => $validated['priority'],
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => 'priority_set',
            'note' => "Prioritas tiket diatur menjadi: {$ticket->priorityLabel()} oleh {$user->name} ({$user->role?->name}).",
        ]);

        return back()->with('success', "Prioritas tiket #{$ticket->ticket_number} diatur ke: {$ticket->priorityLabel()}.");
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'category.initialTargetRole',
            'category.maxApprovalRole',
            'creator.role',
            'creatorRole',
            'currentRole.parent',
            'currentRole.children',
            'assignedUser',
            'logs.user.role',
            'logs.fromRole',
            'logs.toRole',
        ]);

        $user = Auth::user()->load('role.parent');
        $stepper = $ticket->getHierarchyStepper();
        $nextSuperiorRole = $ticket->nextSuperiorRole();
        $subordinateRoles = $ticket->currentRole?->children ?? collect();
        $canHandle = $ticket->canBeHandledBy($user);

        return view('tickets.show', compact(
            'ticket',
            'user',
            'stepper',
            'nextSuperiorRole',
            'subordinateRoles',
            'canHandle'
        ));
    }

    public function handleAction(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');
        $ticket->load(['currentRole.parent', 'currentRole.children', 'category']);

        if (!$ticket->canBeHandledBy($user)) {
            return back()->with('error', 'Anda tidak memiliki wewenang pada posisi hirarki tiket saat ini.');
        }

        $validated = $request->validate([
            'action_type' => ['required', 'in:in_progress,escalate,approve_step,dispose,resolve,reject'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'note' => ['required', 'string', 'min:3'],
            'target_subordinate_role_id' => ['nullable', 'exists:roles,id'],
        ]);

        if (!empty($validated['priority'])) {
            $ticket->priority = $validated['priority'];
            $ticket->save();
        }

        $fromRole = $ticket->currentRole;
        $actionType = $validated['action_type'];
        $note = $validated['note'];

        if ($actionType === 'in_progress') {
            $ticket->update([
                'status' => 'in_progress',
                'assigned_user_id' => $user->id,
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_role_id' => $fromRole->id,
                'to_role_id' => $fromRole->id,
                'action' => 'in_progress',
                'note' => $note,
            ]);

            return back()->with('success', "Tiket ditandai sedang diproses oleh {$fromRole->name}.");
        }

        if ($actionType === 'escalate') {
            $superiorRole = $fromRole->parent;
            if (!$superiorRole) {
                return back()->with('error', "Role {$fromRole->name} tidak memiliki atasan lagi di pohon hirarki.");
            }

            $nextAssignee = User::where('role_id', $superiorRole->id)->first();

            $ticket->update([
                'current_role_id' => $superiorRole->id,
                'assigned_user_id' => $nextAssignee?->id,
                'status' => 'escalated',
                'approval_step' => $ticket->approval_step + 1,
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_role_id' => $fromRole->id,
                'to_role_id' => $superiorRole->id,
                'action' => 'escalated',
                'note' => $note,
            ]);

            return back()->with('success', "Tiket berhasil dieskalasi dari {$fromRole->name} ke Role {$superiorRole->name}.");
        }

        if ($actionType === 'approve_step') {
            if ($ticket->isAtFinalApprovalStage()) {
                $ticket->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);

                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'from_role_id' => $fromRole->id,
                    'to_role_id' => $fromRole->id,
                    'action' => 'final_approved',
                    'note' => $note,
                ]);

                return back()->with('success', "Approval final selesai pada level {$fromRole->name}.");
            }

            $superiorRole = $fromRole->parent;
            $nextAssignee = User::where('role_id', $superiorRole->id)->first();

            $ticket->update([
                'current_role_id' => $superiorRole->id,
                'assigned_user_id' => $nextAssignee?->id,
                'status' => 'waiting_next_approval',
                'approval_step' => $ticket->approval_step + 1,
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_role_id' => $fromRole->id,
                'to_role_id' => $superiorRole->id,
                'action' => 'approved_step',
                'note' => $note,
            ]);

            return back()->with('success', "Disetujui oleh {$fromRole->name} dan diteruskan ke Role {$superiorRole->name}.");
        }

        if ($actionType === 'dispose') {
            $subRoleId = $validated['target_subordinate_role_id'] ?? $fromRole->children->first()?->id;
            $subRole = Role::find($subRoleId);

            if (!$subRole) {
                return back()->with('error', 'Pilih role bawahan untuk tujuan disposisi.');
            }

            $nextAssignee = User::where('role_id', $subRole->id)->first();

            $ticket->update([
                'current_role_id' => $subRole->id,
                'assigned_user_id' => $nextAssignee?->id,
                'status' => 'in_progress',
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_role_id' => $fromRole->id,
                'to_role_id' => $subRole->id,
                'action' => 'disposed',
                'note' => $note,
            ]);

            return back()->with('success', "Tiket didisposisikan ke Role {$subRole->name}.");
        }

        if ($actionType === 'resolve') {
            $ticket->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_role_id' => $fromRole->id,
                'to_role_id' => $fromRole->id,
                'action' => 'resolved',
                'note' => $note,
            ]);

            return back()->with('success', "Tiket #{$ticket->ticket_number} telah diselesaikan.");
        }

        if ($actionType === 'reject') {
            $ticket->update([
                'status' => 'rejected',
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_role_id' => $fromRole->id,
                'to_role_id' => $fromRole->id,
                'action' => 'rejected',
                'note' => $note,
            ]);

            return back()->with('success', "Tiket #{$ticket->ticket_number} telah ditolak.");
        }

        return back();
    }

    public function addComment(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'min:2'],
        ]);

        $user = Auth::user();

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => 'comment',
            'note' => $validated['note'],
        ]);

        return back()->with('success', 'Balasan berhasil dikirim.');
    }
}
