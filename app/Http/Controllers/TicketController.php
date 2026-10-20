<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RolePriorityRoute;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketLog;
use App\Models\TicketProblem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    /**
     * Menu 1: Tiket Saya (Menampilkan tiket saya yang sedang diproses + tombol Buat Tiket Baru via Modal)
     */
    public function myTickets(Request $request)
    {
        $user = Auth::user()->load('role.parent');
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');

        $query = Ticket::with(['category', 'currentRole', 'creatorRole', 'assignedUser', 'logs'])
            ->where('creator_id', $user->id)
            ->whereNotIn('status', ['resolved', 'rejected', 'closed']);

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
            'search',
            'categoryId',
            'categories'
        ));
    }

    /**
     * Menu 2: Riwayat Tiket (Berfokus khusus pada tiket saya yang sudah berstatus Approve / Selesai + Preview Dokumen)
     */
    public function historyTickets(Request $request)
    {
        $user = Auth::user()->load('role.parent');
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');

        $query = Ticket::with(['category', 'currentRole', 'creatorRole', 'assignedUser', 'logs.user.role'])
            ->where('status', 'resolved')
            ->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)
                    ->orWhere('assigned_user_id', $user->id);
                if ($user->role_id) {
                    $q->orWhere('current_role_id', $user->role_id);
                }
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $tickets = $query->latest('resolved_at')->latest()->get();
        $categories = TicketCategory::orderBy('name')->get();

        return view('tickets.history', compact(
            'tickets',
            'user',
            'search',
            'categoryId',
            'categories'
        ));
    }

    /**
     * Menu 3: Tiket Masuk Role (Untuk Dosen / Kaprodi / Dekan / Superuser)
     */
    public function inbox(Request $request)
    {
        $user = Auth::user()->load(['role.parent', 'role.children']);
        $scope = $request->query('scope', 'current_role');
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $statusFilter = $request->query('status');

        $query = Ticket::with(['creator', 'creatorRole', 'currentRole.priorityRoutes.user', 'category', 'assignedUser'])
            ->withCount([
                'problems',
                'problems as done_problems_count' => fn ($problemQuery) => $problemQuery->where('is_done', true),
            ]);

        if ($scope === 'all_system' && $user->isSuperuser()) {
            // Superuser melihat seluruh tiket
        } elseif ($scope === 'history_role') {
            $query->whereHas('logs', function ($q) use ($user) {
                $q->where('from_role_id', $user->role_id);
            });
        } else {
            $query->where('current_role_id', $user->role_id)
                ->whereNotIn('status', ['resolved', 'rejected', 'closed']);
            $headId = $user->role?->head_user_id;
            $isHead = $headId && (int) $headId === (int) $user->id;
            if ($headId && !$isHead && !$user->isSuperuser()) {
                $query->where('assigned_user_id', $user->id);
            }
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
        $inboxStaff = collect();
        $role = $user->role;
        $isHead = $role && $role->head_user_id && (int) $role->head_user_id === (int) $user->id;
        $staffProgress = collect();
        if ($isHead) {
            $inboxStaff = $role->users()->orderBy('name')->limit(100)->get(['id', 'name', 'jabatan', 'keahlian']);
            $staffProgress = $this->staffProgressForRole($role, $user);
        }

        return view('tickets.inbox', compact(
            'tickets',
            'user',
            'scope',
            'search',
            'categoryId',
            'statusFilter',
            'categories',
            'inboxStaff',
            'staffProgress',
            'isHead'
        ));
    }

    public function create()
    {
        return redirect()->route('tickets.my');
    }

    /**
     * Simpan Tiket Baru dari Modal Buat Tiket di Halaman Tiket Saya
     */
    public function store(Request $request)
    {
        $user = Auth::user()->load('role.parent');

        $baseValidated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category_id' => ['required', 'exists:ticket_categories,id'],
            'description' => ['required', 'string', 'min:5'],
            'priority_rank' => ['required', 'integer', 'between:1,4'],
        ]);

        $category = TicketCategory::with('initialTargetRole')->findOrFail($baseValidated['category_id']);

        $attachmentRules = $category->is_attachment_required
            ? ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip', 'max:10240']
            : ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip', 'max:10240'];

        $request->validate([
            'attachment' => $attachmentRules,
        ], [
            'attachment.required' => "Layanan '{$category->name}' mewajibkan Anda mengisi dan mengunggah dokumen template layanan.",
        ]);

        $targetRole = $category->resolveTargetRoleFor($user->role)
            ?? Role::where('is_superuser', true)->first()
            ?? $user->role;

        $assignedUser = null;
        if ($targetRole->head_user_id) {
            $assignedUser = $targetRole->defaultAssignee();
        } elseif ($user->supervisor && (int) $user->supervisor->role_id === (int) $targetRole->id) {
            $assignedUser = $user->supervisor;
        } else {
            $assignedUser = $targetRole->defaultAssignee();
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
            'title' => $baseValidated['title'],
            'description' => $baseValidated['description'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'category_id' => $category->id,
            'priority' => RolePriorityRoute::legacyPriority((int) $baseValidated['priority_rank']),
            'priority_rank' => (int) $baseValidated['priority_rank'],
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
                . ' Prioritas pemohon: ' . RolePriorityRoute::labelFor((int) $baseValidated['priority_rank']) . '.'
                . ($attachmentName ? " Lampiran: {$attachmentName}" : ''),
        ]);

        return redirect()->route('tickets.my')
            ->with('success', "Tiket #{$ticket->ticket_number} berhasil dibuat dan sedang diproses pada Role {$targetRole->name}.");
    }

    /**
     * Preview Template Dokumen Kategori Layanan Secara Inline
     */
    public function previewCategoryTemplate(TicketCategory $category)
    {
        if (!$category->template_path || !Storage::disk('public')->exists($category->template_path)) {
            abort(404, 'Template dokumen layanan tidak ditemukan.');
        }

        $fullPath = Storage::disk('public')->path($category->template_path);
        $mimeType = Storage::disk('public')->mimeType($category->template_path) ?? 'application/octet-stream';
        $filename = $category->template_name ?? basename($category->template_path);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    /**
     * Unduh Template Dokumen Kategori Layanan
     */
    public function downloadCategoryTemplate(TicketCategory $category)
    {
        if (!$category->template_path || !Storage::disk('public')->exists($category->template_path)) {
            abort(404, 'Template dokumen layanan tidak ditemukan.');
        }

        return Storage::disk('public')->download(
            $category->template_path,
            $category->template_name ?? basename($category->template_path)
        );
    }

    /**
     * Preview Dokumen Lampiran Secara Inline
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

    public function updatePriority(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');

        if (!$ticket->canBeHandledBy($user)) {
            return back()->with('error', 'Hanya role penanggung jawab saat ini yang dapat mengatur prioritas tiket.');
        }

        if ($request->filled('assessed_priority_rank')) {
            if (!$ticket->canBeDirectedBy($user)) {
                return back()->with('error', 'Hanya kepala role yang menilai prioritas tiket.');
            }

            $assessed = $request->validate([
                'assessed_priority_rank' => ['required', 'integer', 'between:1,4'],
            ]);

            $rank = (int) $assessed['assessed_priority_rank'];
            $ticket->update(['assessed_priority_rank' => $rank]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_role_id' => $user->role_id,
                'to_role_id' => $ticket->current_role_id,
                'action' => 'priority_set',
                'note' => 'Kepala menilai prioritas menjadi ' . RolePriorityRoute::labelFor($rank) . '.',
            ]);

            return back()->with('success', "Prioritas tiket #{$ticket->ticket_number} dinilai menjadi " . RolePriorityRoute::labelFor($rank) . '.');
        }

        if ($request->filled('priority_rank') || $request->filled('assigned_user_id')) {
            $validatedRank = $request->validate([
                'priority_rank' => ['required', 'integer', 'between:1,8'],
                'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            ]);

            $keptAssignee = !empty($validatedRank['assigned_user_id'])
                && (int) $ticket->assigned_user_id === (int) $validatedRank['assigned_user_id'];

            $error = $this->applyPriorityRank(
                $ticket,
                (int) $validatedRank['priority_rank'],
                $user,
                !empty($validatedRank['assigned_user_id']) ? (int) $validatedRank['assigned_user_id'] : null
            );
            if ($error) {
                return back()->with('error', $error);
            }

            $ticket->refresh()->load('assignedUser');

            if ($keptAssignee) {
                return back()->with('success', "Prioritas tiket #{$ticket->ticket_number} diubah menjadi {$ticket->priorityLabel()}.");
            }

            return back()->with('success', "Prioritas tiket #{$ticket->ticket_number} ({$ticket->priorityLabel()}) diteruskan ke {$ticket->assignedUser?->name}.");
        }

        $validated = $request->validate([
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);

        $ticket->update([
            'priority' => $validated['priority'],
            'priority_rank' => null,
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
            'currentRole.priorityRoutes.user',
            'assignedUser',
            'problems.doneBy',
            'logs.user.role',
            'logs.fromRole',
            'logs.toRole',
        ]);

        $user = Auth::user()->load('role.parent');
        $stepper = $ticket->getHierarchyStepper();
        $nextSuperiorRole = $ticket->nextSuperiorRole();
        $subordinateRoles = $ticket->previousRolesInChain();
        $canHandle = $ticket->canBeHandledBy($user);
        $roleStaff = collect();
        $canDirect = $ticket->canBeDirectedBy($user);
        if ($canDirect && $ticket->currentRole?->head_user_id) {
            $roleStaff = User::where('role_id', $ticket->current_role_id)->orderBy('name')->limit(100)->get(['id', 'name', 'jabatan', 'keahlian']);
        }

        return view('tickets.show', compact(
            'ticket',
            'user',
            'stepper',
            'nextSuperiorRole',
            'subordinateRoles',
            'canHandle',
            'canDirect',
            'roleStaff'
        ));
    }

    public function handleAction(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');
        $ticket->load(['currentRole.parent', 'currentRole.children', 'creatorRole', 'category']);

        if (!$ticket->canBeHandledBy($user)) {
            return back()->with('error', 'Anda tidak memiliki wewenang pada posisi hirarki tiket saat ini.');
        }

        if ($request->filled('progress_percent') && $ticket->canReportProgress($user)) {
            $reportedPercent = (int) $request->validate([
                'progress_percent' => ['required', 'integer', 'between:0,100'],
            ])['progress_percent'];
            $this->recordReportedProgress($ticket, $user, $reportedPercent);
        }

        $validated = $request->validate([
            'action_type' => ['required', 'in:in_progress,escalate,approve_step,dispose,resolve,reject'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'priority_rank' => ['nullable', 'integer', 'between:1,8'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'note' => $request->input('action_type') === 'approve_step'
                ? ['nullable', 'string', 'max:1000']
                : ['required', 'string', 'min:3'],
            'target_subordinate_role_id' => ['nullable', 'exists:roles,id'],
        ]);

        if (!empty($validated['priority_rank']) && $validated['action_type'] !== 'reject') {
            $error = $this->applyPriorityRank(
                $ticket,
                (int) $validated['priority_rank'],
                $user,
                !empty($validated['assigned_user_id']) ? (int) $validated['assigned_user_id'] : null
            );
            if ($error) {
                return back()->with('error', $error);
            }
        } elseif (!empty($validated['priority'])) {
            $ticket->priority = $validated['priority'];
            $ticket->priority_rank = null;
            $ticket->save();
        }

        $fromRole = $ticket->currentRole;
        $actionType = $validated['action_type'];
        $note = trim((string) ($validated['note'] ?? ''));
        if ($note === '' && $actionType === 'approve_step') {
            $note = 'Disetujui.';
        }

        if ($actionType === 'in_progress') {
            $ticket->update([
                'status' => 'in_progress',
                'assigned_user_id' => $ticket->assigned_user_id ?: $user->id,
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
            $superiorRole = $ticket->nextSuperiorRole();
            if (!$superiorRole) {
                return back()->with('error', "Role {$fromRole->name} sudah berada di puncak tahapan hirarki layanan ini.");
            }

            $nextAssignee = $superiorRole->defaultAssignee();

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

            $superiorRole = $ticket->nextSuperiorRole();
            if (!$superiorRole) {
                return back()->with('error', 'Tahap hirarki berikutnya tidak ditemukan.');
            }

            $nextAssignee = $superiorRole->defaultAssignee();

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
            $subRoleId = $validated['target_subordinate_role_id'] ?? $ticket->previousRolesInChain()->first()?->id;
            $subRole = Role::find($subRoleId);

            if (!$subRole) {
                return back()->with('error', 'Pilih role tahap sebelumnya untuk tujuan disposisi.');
            }

            $nextAssignee = $subRole->defaultAssignee();

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

    public function reopen(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');
        $ticket->load('currentRole');

        if (!$ticket->canBeRevisedBy($user)) {
            return back()->with('error', 'Hanya kepala yang dapat mengedit tiket yang sudah selesai.');
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $ticket->update([
            'status' => 'in_progress',
            'resolved_at' => null,
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => 'reopened',
            'note' => 'Kepala membuka lagi tiket selesai untuk diperbaiki: ' . $validated['note'],
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Tiket dibuka lagi. Staff dapat memperbaiki bagian yang belum sesuai.');
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

    public function storeProblems(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');
        $ticket->load('currentRole');

        if (!$ticket->canBeDirectedBy($user)) {
            return back()->with('error', 'Hanya kepala yang dapat menetapkan masalah pada tiket ini.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:2000'],
        ]);

        $lines = collect(preg_split('/\r\n|\r|\n/', $validated['title']))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values();

        if ($lines->isEmpty()) {
            return back()->with('error', 'Tuliskan minimal satu masalah.');
        }

        if ($lines->count() > 20) {
            return back()->with('error', 'Maksimal 20 masalah sekaligus.');
        }

        $tooLong = $lines->first(fn ($line) => mb_strlen($line) > 200);
        if ($tooLong) {
            return back()->with('error', 'Setiap masalah maksimal 200 karakter.');
        }

        $nextOrder = (int) $ticket->problems()->max('sort_order');
        foreach ($lines as $line) {
            $nextOrder++;
            TicketProblem::create([
                'ticket_id' => $ticket->id,
                'title' => $line,
                'sort_order' => $nextOrder,
            ]);
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => 'problems_set',
            'note' => 'Kepala menetapkan ' . $lines->count() . ' masalah: ' . $lines->implode('; ') . '.',
        ]);

        $ticket->load('problems');

        return back()->with('success', 'Masalah tiket ditetapkan. Staff mengisi persen pengerjaan secara manual.');
    }

    public function destroyProblem(Ticket $ticket, TicketProblem $problem)
    {
        $user = Auth::user()->load('role');
        $ticket->load('currentRole');

        if ((int) $problem->ticket_id !== (int) $ticket->id) {
            abort(404);
        }

        if (!$ticket->canBeDirectedBy($user)) {
            return back()->with('error', 'Hanya kepala yang dapat menghapus masalah.');
        }

        $title = $problem->title;
        $problem->delete();
        $ticket->unsetRelation('problems');
        $ticket->load('problems');

        return back()->with('success', "Masalah \"{$title}\" dihapus.");
    }

    public function toggleProblem(Ticket $ticket, TicketProblem $problem)
    {
        $user = Auth::user()->load('role');
        $ticket->load('currentRole');

        if ((int) $problem->ticket_id !== (int) $ticket->id) {
            abort(404);
        }

        if (!$ticket->canUpdateProblemProgress($user)) {
            return back()->with('error', 'Anda tidak dapat mengubah progres masalah pada tiket ini.');
        }

        $done = !$problem->is_done;
        $problem->update([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
            'done_by' => $done ? $user->id : null,
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => $done ? 'problem_done' : 'problem_reopened',
            'note' => ($done ? 'Masalah selesai: ' : 'Masalah dibuka lagi: ') . $problem->title . '.',
        ]);

        return back()->with('success', $done ? 'Masalah ditandai selesai.' : 'Masalah dibuka lagi.');
    }

    public function updateProgress(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');
        $ticket->load('currentRole');

        if (!$ticket->canReportProgress($user)) {
            return back()->with('error', 'Hanya staff yang ditugaskan yang dapat mengisi persentase pengerjaan.');
        }

        $validated = $request->validate([
            'progress_percent' => ['required', 'integer', 'between:0,100'],
            'note' => ['nullable', 'string', 'max:500'],
            'work_status' => ['nullable', 'in:delivered,in_progress,blocked,finished'],
        ]);

        $percent = (int) $validated['progress_percent'];
        $note = trim((string) ($validated['note'] ?? ''));
        $workStatus = $validated['work_status'] ?? null;
        if ($workStatus === 'blocked' && $note === '') {
            return back()->with('error', 'Blocked berarti ada masalah saat pengerjaan. Tuliskan masalahnya di catatan.')->withInput();
        }

        $this->recordReportedProgress($ticket, $user, $percent, $note, $workStatus);

        return back()->with('success', 'Progres ' . $percent . '% tercatat. Kepala dapat memantau angka ini.');
    }

    private function recordReportedProgress(Ticket $ticket, User $user, int $percent, ?string $note = null, ?string $workStatus = null): void
    {
        $note = $note !== null ? trim($note) : null;
        $storedNote = $note === '' ? null : $note;
        $percentUnchanged = $ticket->progress_percent !== null && (int) $ticket->progress_percent === $percent;
        $noteUnchanged = $note === null || (string) ($ticket->progress_note ?? '') === (string) ($storedNote ?? '');
        $workUnchanged = $workStatus === null || (string) $ticket->work_status === $workStatus;

        if ($percentUnchanged && $noteUnchanged && $workUnchanged) {
            return;
        }

        $changes = ['progress_percent' => $percent];
        if ($note !== null) {
            $changes['progress_note'] = $storedNote;
        }
        if ($workStatus !== null) {
            $changes['work_status'] = $workStatus;
        }
        $ticket->update($changes);

        $logNote = 'Staff melaporkan progres pengerjaan ' . $percent . '%.';
        if ($workStatus) {
            $logNote .= ' Status: ' . $ticket->workStatusLabel() . '.';
        }
        if ($storedNote) {
            $logNote .= ' Catatan: ' . $storedNote;
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => 'progress_reported',
            'note' => $logNote,
        ]);
    }

    private function staffProgressForRole(Role $role, User $head)
    {
        $staffUsers = User::where('role_id', $role->id)
            ->where('id', '!=', $head->id)
            ->orderBy('name')
            ->get(['id', 'name', 'keahlian']);

        $openTickets = Ticket::query()
            ->where('current_role_id', $role->id)
            ->whereIn('assigned_user_id', $staffUsers->pluck('id'))
            ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
            ->withCount([
                'problems',
                'problems as done_problems_count' => fn ($problemQuery) => $problemQuery->where('is_done', true),
            ])
            ->get()
            ->groupBy('assigned_user_id');

        return $staffUsers->map(function (User $staff) use ($openTickets) {
            $assigned = $openTickets->get($staff->id, collect())
                ->sortBy([
                    fn (Ticket $left, Ticket $right) => ($left->assessed_priority_rank ?? 0) <=> ($right->assessed_priority_rank ?? 0),
                    fn (Ticket $left, Ticket $right) => ($left->created_at?->getTimestamp() ?? PHP_INT_MAX) <=> ($right->created_at?->getTimestamp() ?? PHP_INT_MAX),
                ])
                ->values();
            $reported = $assigned->filter(fn (Ticket $ticket) => $ticket->progress_percent !== null);
            $latestNote = $reported->sortByDesc('updated_at')->first(fn (Ticket $ticket) => filled($ticket->progress_note));

            return [
                'user' => $staff,
                'tickets' => $assigned->count(),
                'open_tickets' => $assigned,
                'reported' => $reported->count(),
                'percent' => $reported->isEmpty() ? null : (int) round($reported->avg('progress_percent')),
                'blocked' => $assigned->where('work_status', 'blocked')->count(),
                'note' => $latestNote?->progress_note,
            ];
        });
    }

    private function applyPriorityRank(Ticket $ticket, int $rank, User $actor, ?int $chosenStaffId = null): ?string
    {
        if (!$chosenStaffId) {
            return 'Tinjau permintaan tiket dulu, lalu pilih staff yang keahliannya cocok.';
        }

        $staff = User::whereKey($chosenStaffId)->where('role_id', $ticket->current_role_id)->first();
        if (!$staff) {
            return 'Staff yang dipilih bukan anggota role ini.';
        }

        if ((int) $ticket->priority_rank === $rank && (int) $ticket->assigned_user_id === (int) $staff->id) {
            return null;
        }

        $ticket->update([
            'priority_rank' => $rank,
            'priority' => RolePriorityRoute::legacyPriority($rank),
            'assigned_user_id' => $staff->id,
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'from_role_id' => $actor->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => 'priority_set',
            'note' => 'Setelah ditinjau, prioritas ' . RolePriorityRoute::labelFor($rank)
                . ' diarahkan ke ' . $staff->name
                . ($staff->keahlian ? ' (keahlian: ' . $staff->keahlian . ')' : '')
                . ' oleh ' . $actor->name . '.',
        ]);

        return null;
    }
}
