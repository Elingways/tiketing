<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Daftar Tiket Masuk ke Role User Saat Ini (Contoh: Tiket dari Mahasiswa masuk ke Dosen, atau dari Dosen masuk ke Kaprodi)
     */
    public function inbox(Request $request)
    {
        $user = Auth::user()->load(['role.parent', 'role.children']);
        $scope = $request->query('scope', 'current_role'); // current_role, history_role, all_system
        $statusFilter = $request->query('status');

        $query = Ticket::with(['creator', 'creatorRole', 'currentRole', 'category', 'assignedUser']);

        if ($scope === 'all_system' && $user->isSuperuser()) {
            // Superuser melihat seluruh tiket lintas hirarki
        } elseif ($scope === 'history_role') {
            // Tiket yang pernah melewati/dieskalasi dari role ini ke atasan
            $query->whereHas('logs', function ($q) use ($user) {
                $q->where('from_role_id', $user->role_id);
            });
        } else {
            // Default: Tiket yang saat ini berada di meja Role user ini
            $query->where('current_role_id', $user->role_id);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $tickets = $query->latest()->get();

        return view('tickets.inbox', compact('tickets', 'user', 'scope', 'statusFilter'));
    }

    /**
     * Daftar Tiket yang Diajukan oleh User Saat Ini
     */
    public function myTickets()
    {
        $user = Auth::user()->load('role.parent');
        $tickets = Ticket::with(['category', 'currentRole', 'creatorRole', 'assignedUser'])
            ->where('creator_id', $user->id)
            ->latest()
            ->get();

        return view('tickets.my', compact('tickets', 'user'));
    }

    /**
     * Form Buat Tiket Baru dengan Preview Otomatis Alur Hirarki
     */
    public function create()
    {
        $user = Auth::user()->load('role.parent');
        $categories = TicketCategory::with(['initialTargetRole.parent', 'maxApprovalRole'])
            ->where('is_active', true)
            ->get();

        // Siapkan informasi alur hirarki tiap kategori untuk ditampilkan interaktif dengan Alpine.js
        $categoryChains = [];
        foreach ($categories as $cat) {
            $chain = $cat->getWorkflowChain($user->role);
            $categoryChains[$cat->id] = [
                'workflow_type' => $cat->workflow_type,
                'workflow_label' => $cat->workflow_type === 'tiered_approval'
                    ? 'Approval Berjenjang Otomatis'
                    : 'Eskalasi / Disposisi Dinamis',
                'target_role' => $chain->first()?->name ?? ($user->role?->parent?->name ?? 'Admin'),
                'chain_names' => array_merge(
                    [$user->role?->name ?? 'Pemohon'],
                    $chain->pluck('name')->toArray()
                ),
                'description' => $cat->description,
            ];
        }

        return view('tickets.create', compact('user', 'categories', 'categoryChains'));
    }

    /**
     * Simpan Tiket Baru & Arahkan ke Role Tujuan Sesuai Hirarki
     */
    public function store(Request $request)
    {
        $user = Auth::user()->load('role.parent');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category_id' => ['required', 'exists:ticket_categories,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'description' => ['required', 'string', 'min:10'],
        ]);

        $category = TicketCategory::with('initialTargetRole')->findOrFail($validated['category_id']);

        // Tentukan role tujuan awal: dari konfigurasi kategori, atau otomatis ke parent_id dari role user pembuat tiket!
        $targetRole = $category->resolveTargetRoleFor($user->role)
            ?? Role::where('is_superuser', true)->first()
            ?? $user->role;

        // Jika user punya supervisor langsung yang rolenya sama dengan $targetRole (misal Dosen Wali), otomatis assign ke beliau
        $assignedUser = null;
        if ($user->supervisor && (int) $user->supervisor->role_id === (int) $targetRole->id) {
            $assignedUser = $user->supervisor;
        } else {
            $assignedUser = User::where('role_id', $targetRole->id)->first();
        }

        $nextSequence = Ticket::count() + 1;
        $ticketNumber = sprintf('TKT-%s-%04d', now()->format('Ym'), $nextSequence);

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $category->id,
            'priority' => $validated['priority'],
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
            'note' => "Tiket diajukan oleh {$user->name} ({$user->role?->name}) dan masuk ke dashboard role {$targetRole->name}.",
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket #{$ticket->ticket_number} berhasil dibuat dan diteruskan ke Role {$targetRole->name}!");
    }

    /**
     * Detail Tiket, Visual Stepper Hirarki, & Eksekusi Aksi (Eskalasi / Approval / Disposisi)
     */
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

    /**
     * Eksekusi Aksi Hirarki pada Tiket (Mulai Proses, Eskalasi ke Atasan, Approve Berjenjang, Disposisi, Selesaikan, Tolak)
     */
    public function handleAction(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');
        $ticket->load(['currentRole.parent', 'currentRole.children', 'category']);

        if (!$ticket->canBeHandledBy($user)) {
            return back()->with('error', 'Anda tidak memiliki wewenang pada posisi hirarki tiket saat ini.');
        }

        $validated = $request->validate([
            'action_type' => ['required', 'in:in_progress,escalate,approve_step,dispose,resolve,reject'],
            'note' => ['required', 'string', 'min:3'],
            'target_subordinate_role_id' => ['nullable', 'exists:roles,id'],
        ]);

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
                'note' => "[Eskalasi Hirarki {$fromRole->name} → {$superiorRole->name}]: " . $note,
            ]);

            return back()->with('success', "Tiket berhasil dieskalasi dari {$fromRole->name} ke atasan hirarki: {$superiorRole->name}!");
        }

        if ($actionType === 'approve_step') {
            // Cek apakah ini tahap akhir atau masih harus naik ke role atasan berikutnya
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
                    'note' => "[Approval Final oleh {$fromRole->name}]: " . $note,
                ]);

                return back()->with('success', "Approval final selesai pada level {$fromRole->name}. Tiket kini berstatus Selesai/Disetujui!");
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
                'note' => "[Disetujui oleh {$fromRole->name}, diteruskan ke {$superiorRole->name}]: " . $note,
            ]);

            return back()->with('success', "Disetujui pada tingkat {$fromRole->name}! Tiket otomatis naik ke hirarki berikutnya: {$superiorRole->name}.");
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
                'note' => "[Disposisi dari {$fromRole->name} → {$subRole->name}]: " . $note,
            ]);

            return back()->with('success', "Tiket berhasil didisposisikan ke role {$subRole->name}.");
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

            return back()->with('success', "Tiket #{$ticket->ticket_number} telah diselesaikan oleh {$fromRole->name}!");
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

    /**
     * Tambahkan Komentar / Balasan Diskusi pada Tiket
     */
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

        return back()->with('success', 'Balasan berhasil dikirim ke ruang diskusi tiket.');
    }
}
