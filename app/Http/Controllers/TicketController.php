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
     * Laporan performa bulanan untuk kepala role: progres staff dan status pengerjaan.
     */
    public function monthlyReport(Request $request)
    {
        $user = Auth::user()->load('role');
        $month = (string) $request->query('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        $start = \Carbon\Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $isHead = $user->role && (int) $user->role->head_user_id === (int) $user->id;
        $allowed = $isHead || $user->isSuperuser();

        $role = $user->role;
        if ($allowed && $user->isSuperuser() && $request->filled('role_id')) {
            $role = Role::find($request->query('role_id')) ?? $role;
        }

        $tickets = collect();
        $staffRows = collect();
        $summary = [
            'tickets' => 0,
            'percent' => null,
            'delivered' => 0,
            'in_progress' => 0,
            'blocked' => 0,
            'finished' => 0,
            'resolved' => 0,
            'rejected' => 0,
        ];

        if ($allowed && $role) {
            $tickets = Ticket::with(['assignedUser', 'category', 'creator'])
                ->where('current_role_id', $role->id)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('delivered_at', [$start, $end])
                        ->orWhereBetween('finished_at', [$start, $end])
                        ->orWhereBetween('resolved_at', [$start, $end])
                        ->orWhere(function ($active) use ($start, $end) {
                            $active->whereBetween('updated_at', [$start, $end])
                                ->where(function ($unfinished) use ($end) {
                                    $unfinished->whereNull('finished_at')->orWhere('finished_at', '>', $end);
                                });
                        });
                })
                ->latest('updated_at')
                ->get()
                ->map(function (Ticket $ticket) use ($start, $end) {
                    $ticket->report_status = $this->reportWorkStatus($ticket, $start, $end);

                    return $ticket;
                })
                ->filter(fn (Ticket $ticket) => $ticket->report_status !== null)
                ->values();

            $headId = (int) $role->head_user_id;
            $summary['tickets'] = $tickets->count();
            $reported = $tickets->filter(fn (Ticket $ticket) => $ticket->progress_percent !== null);
            $summary['percent'] = $reported->isEmpty() ? null : (int) round($reported->avg('progress_percent'));
            foreach (['delivered', 'in_progress', 'blocked', 'finished'] as $workStatus) {
                $summary[$workStatus] = $tickets->where('report_status', $workStatus)->count();
            }
            $summary['resolved'] = $tickets->filter(fn (Ticket $ticket) => $ticket->resolved_at && $ticket->resolved_at->between($start, $end))->count();
            $summary['rejected'] = $tickets->where('status', 'rejected')->count();

            $staffRows = $tickets
                ->groupBy(fn (Ticket $ticket) => $ticket->assigned_user_id && (int) $ticket->assigned_user_id !== $headId
                    ? (int) $ticket->assigned_user_id
                    : 0)
                ->map(function ($group, $staffId) use ($start, $end) {
                    $reported = $group->filter(fn (Ticket $ticket) => $ticket->progress_percent !== null);

                    return [
                        'name' => $staffId ? ($group->first()->assignedUser?->name ?? 'Staff') : 'Belum dibagikan',
                        'tickets' => $group->count(),
                        'percent' => $reported->isEmpty() ? null : (int) round($reported->avg('progress_percent')),
                        'blocked' => $group->where('report_status', 'blocked')->count(),
                        'in_progress' => $group->where('report_status', 'in_progress')->count(),
                        'finished' => $group->where('report_status', 'finished')->count(),
                        'delivered' => $group->where('report_status', 'delivered')->count(),
                        'resolved' => $group->filter(fn (Ticket $ticket) => $ticket->resolved_at && $ticket->resolved_at->between($start, $end))->count(),
                        'items' => $group->sortByDesc(fn (Ticket $ticket) => $ticket->progressPercent() ?? -1)->map(fn (Ticket $ticket) => [
                            'number' => $ticket->ticket_number,
                            'title' => $ticket->title,
                            'percent' => $ticket->progressPercent() ?? 0,
                            'status' => $ticket->report_status,
                        ])->values(),
                    ];
                })
                ->sortBy([
                    ['blocked', 'desc'],
                    ['name', 'asc'],
                ])
                ->values();
        }

        return view('tickets.report', [
            'user' => $user,
            'allowed' => $allowed,
            'role' => $role,
            'month' => $month,
            'monthLabel' => $start->locale('id')->translatedFormat('F Y'),
            'prevMonth' => $start->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $start->copy()->addMonth()->format('Y-m'),
            'tickets' => $tickets,
            'staffRows' => $staffRows,
            'summary' => $summary,
        ]);
    }

    /**
     * Status yang dicatat pada bulan laporan. Finished hanya masuk di bulan tiket itu selesai.
     */
    private function reportWorkStatus(Ticket $ticket, \Carbon\Carbon $start, \Carbon\Carbon $end): ?string
    {
        $finished = $ticket->finished_at;
        $delivered = $ticket->delivered_at;

        if ($finished && $finished->between($start, $end)) {
            return 'finished';
        }

        $stillOpen = !$finished || $finished->gt($end);
        $touchedThisMonth = ($delivered && $delivered->between($start, $end))
            || ($ticket->created_at && $ticket->created_at->between($start, $end))
            || ($ticket->updated_at && $ticket->updated_at->between($start, $end) && $stillOpen);

        if (!$stillOpen || !$touchedThisMonth) {
            return null;
        }

        if ($delivered && $delivered->between($start, $end)) {
            return 'delivered';
        }

        if ($ticket->work_status === 'blocked') {
            return 'blocked';
        }

        return $ticket->work_status ?: 'in_progress';
    }

    /**
     * Papan pekerjaan yang sedang dikerjakan tiap divisi, untuk pemohon tiket.
     */
    public function divisionWorkload()
    {
        $user = Auth::user()->load('role');
        $groups = Ticket::with(['currentRole', 'assignedUser', 'category'])
            ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
            ->latest('updated_at')
            ->get()
            ->groupBy(fn (Ticket $ticket) => $ticket->currentRole?->name ?? 'Tanpa divisi')
            ->sortKeys();

        return view('tickets.workload', compact('user', 'groups'));
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
        $priorityFilter = $request->query('priority');
        $staffFilter = $request->query('assigned_user_id');

        $query = Ticket::with(['creator', 'creatorRole', 'currentRole.priorityRoutes.user', 'category', 'assignedUser'])
            ->withCount([
                'problems',
                'problems as done_problems_count' => fn ($problemQuery) => $problemQuery->where('is_done', true),
            ]);

        if ($scope === 'all_system' && ($user->isSuperuser() || $user->role?->slug === 'admin-it')) {
            // Superuser atau Admin IT melihat seluruh tiket
        } elseif ($scope === 'history_role') {
            $query->whereHas('logs', function ($q) use ($user) {
                $q->where('from_role_id', $user->role_id);
            });
        } else {
            $headId = $user->role?->head_user_id;
            $isHead = $headId && (int) $headId === (int) $user->id;
            if ($isHead) {
                $query->where('current_role_id', $user->role_id)
                    ->whereNotIn('status', ['resolved', 'rejected', 'closed']);
            } elseif ($user->role?->slug === 'admin-it') {
                $itRoleIds = Role::whereIn('slug', ['admin-it', 'supervisor-it'])->pluck('id')->toArray();
                $query->whereIn('current_role_id', $itRoleIds)
                    ->whereNotIn('status', ['resolved', 'rejected', 'closed']);
            } elseif ($headId && !$user->isSuperuser()) {
                $query->where('assigned_user_id', $user->id)
                    ->whereNotIn('status', ['resolved', 'rejected', 'closed']);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('current_role_id', $user->role_id)
                        ->orWhere('assigned_user_id', $user->id);
                })->whereNotIn('status', ['resolved', 'rejected', 'closed']);
            }
        }

        // Stats calculation based on scoped query before extra filters
        $statsBaseQuery = clone $query;
        $openTicketsCount = (clone $statsBaseQuery)->where('status', 'open')->count();
        $inProgressCount = (clone $statsBaseQuery)->where('status', 'in_progress')->count();
        $pendingCount = (clone $statsBaseQuery)->where(function ($q) {
            $q->whereNull('assigned_user_id')->orWhere('status', 'open');
        })->count();
        $totalTicketsCount = (clone $statsBaseQuery)->count();

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

        if ($priorityFilter) {
            $query->where(function ($q) use ($priorityFilter) {
                $q->where('priority', $priorityFilter);
                if (in_array($priorityFilter, ['urgent', 'high', 'medium', 'low'])) {
                    $rankMap = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
                    $rank = $rankMap[$priorityFilter];
                    $q->orWhere('priority_rank', $rank)
                      ->orWhere('assessed_priority_rank', $rank);
                }
            });
        }

        if ($staffFilter) {
            if ($staffFilter === 'unassigned') {
                $query->whereNull('assigned_user_id');
            } else {
                $query->where('assigned_user_id', $staffFilter);
            }
        }

        $tickets = $query->latest()->get();
        $categories = TicketCategory::orderBy('name')->get();
        $inboxStaff = collect();
        $role = $user->role;
        $isHead = $user->isHeadOfRole();
        $isAdmin = $user->isAdminIt();
        $isStaff = $user->isStaff();
        $staffProgress = collect();

        $roleType = 'general';
        if ($isAdmin) {
            $roleType = 'admin';
        } elseif ($isHead) {
            $roleType = 'head';
        } elseif ($isStaff) {
            $roleType = 'staff';
        }

        // Hitung data staff & progress untuk Kepala Staff, Admin IT, dan Staff
        $targetDepartmentRole = null;
        if ($isHead) {
            $targetDepartmentRole = $role;
        } elseif ($isAdmin || $isStaff) {
            $spvRole = Role::where('slug', 'supervisor-it')->first();
            $targetDepartmentRole = $spvRole ?? $role;
        }

        if ($targetDepartmentRole) {
            $inboxStaff = $targetDepartmentRole->assignableStaffUsers();
            $staffProgress = $this->staffProgressForRole($targetDepartmentRole, $user);
        }

        // Tiket yang ditugaskan khusus ke user saat ini (untuk Staff Dashboard)
        $myAssignedTickets = Ticket::with(['creator', 'creatorRole', 'category', 'assignedUser'])
            ->withCount([
                'problems',
                'problems as done_problems_count' => fn ($problemQuery) => $problemQuery->where('is_done', true),
            ])
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
            ->latest()
            ->get();

        $myActiveCount = $myAssignedTickets->count();
        $myResolvedCount = Ticket::where('assigned_user_id', $user->id)->where('status', 'resolved')->count();
        $myAvgPercent = $myAssignedTickets->filter(fn ($t) => $t->progress_percent !== null)->avg('progress_percent');

        return view('tickets.inbox', compact(
            'tickets',
            'user',
            'roleType',
            'scope',
            'search',
            'categoryId',
            'statusFilter',
            'priorityFilter',
            'staffFilter',
            'categories',
            'inboxStaff',
            'staffProgress',
            'isHead',
            'isAdmin',
            'isStaff',
            'openTicketsCount',
            'inProgressCount',
            'pendingCount',
            'totalTicketsCount',
            'myAssignedTickets',
            'myActiveCount',
            'myResolvedCount',
            'myAvgPercent'
        ));
    }

    /**
     * Halaman Menu Khusus: Monitoring Staff Progres & Beban Kerja Tim
     */
    public function staffProgressPage(Request $request)
    {
        $user = Auth::user()->load(['role.parent', 'role.children']);
        $role = $user->role;
        $isHead = $user->isHeadOfRole();
        $isAdmin = $user->isAdminIt();
        $isStaff = $user->isStaff();

        $targetDepartmentRole = null;
        if ($isHead) {
            $targetDepartmentRole = $role;
        } elseif ($isAdmin || $isStaff) {
            $spvRole = Role::where('slug', 'supervisor-it')->first();
            $targetDepartmentRole = $spvRole ?? $role;
        } else {
            $targetDepartmentRole = $role;
        }

        $inboxStaff = $targetDepartmentRole ? $targetDepartmentRole->assignableStaffUsers() : collect();
        $staffProgress = $targetDepartmentRole ? $this->staffProgressForRole($targetDepartmentRole, $user) : collect();

        $totalStaffCount = $staffProgress->count();
        $activeStaffCount = $staffProgress->filter(fn ($p) => $p['tickets'] > 0)->count();
        $totalTicketsHandled = $staffProgress->sum('tickets');
        $blockedCount = $staffProgress->sum(fn ($p) => $p['blocked'] ?? 0);
        $avgProgress = $staffProgress->filter(fn ($p) => $p['percent'] !== null)->avg('percent');

        // Filter per staff spesifik jika dipilih
        $selectedStaffId = $request->query('staff_id');
        $selectedStaff = $selectedStaffId ? $staffProgress->firstWhere('user.id', (int) $selectedStaffId) : null;

        return view('tickets.staff-progress', compact(
            'user',
            'role',
            'isHead',
            'isAdmin',
            'isStaff',
            'inboxStaff',
            'staffProgress',
            'totalStaffCount',
            'activeStaffCount',
            'totalTicketsHandled',
            'blockedCount',
            'avgProgress',
            'selectedStaffId',
            'selectedStaff'
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
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Hanya role penanggung jawab saat ini yang dapat mengatur prioritas tiket.'], 403);
            }
            return back()->with('error', 'Hanya role penanggung jawab saat ini yang dapat mengatur prioritas tiket.');
        }

        if ($request->filled('assessed_priority_rank')) {
            if (!$ticket->canBeDirectedBy($user)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Hanya kepala role yang menilai prioritas tiket.'], 403);
                }
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

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Prioritas tiket #{$ticket->ticket_number} dinilai menjadi " . RolePriorityRoute::labelFor($rank) . '.',
                    'priority' => $ticket->priority,
                    'priority_label' => RolePriorityRoute::labelFor($rank),
                ]);
            }

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
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $error], 422);
                }
                return back()->with('error', $error);
            }

            $ticket->refresh()->load('assignedUser');

            $msg = $keptAssignee
                ? "Prioritas tiket #{$ticket->ticket_number} diubah menjadi {$ticket->priorityLabel()}."
                : "Prioritas tiket #{$ticket->ticket_number} ({$ticket->priorityLabel()}) diteruskan ke {$ticket->assignedUser?->name}.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->priorityLabel(),
                    'assigned_user' => $ticket->assignedUser?->name,
                ]);
            }

            return back()->with('success', $msg);
        }

        $validated = $request->validate([
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);

        $rankMap = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        $mappedRank = $rankMap[$validated['priority']] ?? 3;

        $ticket->update([
            'priority' => $validated['priority'],
            'priority_rank' => $mappedRank,
            'assessed_priority_rank' => $mappedRank,
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => 'priority_set',
            'note' => "Prioritas tiket diatur menjadi: {$ticket->priorityLabel()} oleh {$user->name} ({$user->role?->name}).",
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Prioritas tiket #{$ticket->ticket_number} diatur ke: {$ticket->priorityLabel()}.",
                'priority' => $ticket->priority,
                'priority_label' => $ticket->priorityLabel(),
            ]);
        }

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
            $roleStaff = $ticket->currentRole->assignableStaffUsers();
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

        // Jika Supervisor ikut menginput poin detail pekerjaan + % bobot saat men-deliver ke staff
        if ($ticket->canBeDirectedBy($user) && is_array($request->input('problem_items'))) {
            $this->saveStructuredProblemItems($ticket, $user, $request->input('problem_items', []));
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
            $chosenAssigneeId = !empty($validated['assigned_user_id'])
                ? (int) $validated['assigned_user_id']
                : ($ticket->assigned_user_id ?: $user->id);

            $updates = [
                'status' => 'in_progress',
                'assigned_user_id' => $chosenAssigneeId,
            ];
            $assignedStaff = User::find($chosenAssigneeId);
            if ($assignedStaff) {
                $ticket->loadMissing('currentRole');
                $updates = array_merge($updates, $ticket->deliveryStamp($user, $assignedStaff));
            }

            $ticket->update($updates);
            $ticket->load('problems');
            if ($ticket->hasWeightedProblems()) {
                $ticket->recalculateProgressFromProblems();
            }

            $deliveryNote = ($assignedStaff && (int) $assignedStaff->id !== (int) $user->id)
                ? "Tugas di-deliver oleh {$user->name} ke staff {$assignedStaff->name}" . ($assignedStaff->keahlian ? " ({$assignedStaff->keahlian})" : '') . ". Catatan: {$note}"
                : $note;

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_role_id' => $fromRole->id,
                'to_role_id' => $fromRole->id,
                'action' => 'in_progress',
                'note' => $deliveryNote,
            ]);

            return back()->with(
                'success',
                ($assignedStaff && (int) $assignedStaff->id !== (int) $user->id)
                    ? "Tiket berhasil di-deliver ke {$assignedStaff->name} beserta poin pekerjaan."
                    : "Tiket ditandai sedang diproses oleh {$fromRole->name}."
            );
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

        // Mode 1: Input terstruktur (items[][title] & items[][weight_percent]) dengan bobot %
        if (is_array($request->input('items'))) {
            $savedCount = $this->saveStructuredProblemItems($ticket, $user, $request->input('items', []));
            if ($savedCount === 0) {
                return back()->with('error', 'Tuliskan minimal satu poin detail pekerjaan beserta bobot %-nya.');
            }

            return back()->with('success', "{$savedCount} poin pekerjaan beserta bobot % berhasil disimpan. Saat staff mencentang poin, progres % otomatis bertambah.");
        }

        // Mode 2: Input multiline (title)
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
                'weight_percent' => 0,
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

    /**
     * Simpan daftar poin pekerjaan terstruktur beserta bobot % penyelesaian (weight_percent)
     */
    private function saveStructuredProblemItems(Ticket $ticket, User $user, array $rawItems): int
    {
        $items = collect($rawItems)
            ->map(function ($row) {
                $title = trim((string) ($row['title'] ?? ''));
                $weight = isset($row['weight_percent']) && $row['weight_percent'] !== ''
                    ? max(0, min(100, (int) $row['weight_percent']))
                    : 0;
                return ['title' => $title, 'weight_percent' => $weight];
            })
            ->filter(fn ($row) => $row['title'] !== '')
            ->values();

        if ($items->isEmpty()) {
            return 0;
        }

        $nextOrder = (int) $ticket->problems()->max('sort_order');
        $labels = [];
        foreach ($items as $item) {
            $nextOrder++;
            TicketProblem::create([
                'ticket_id' => $ticket->id,
                'title' => mb_substr($item['title'], 0, 200),
                'weight_percent' => $item['weight_percent'],
                'sort_order' => $nextOrder,
            ]);
            $labels[] = $item['title'] . ($item['weight_percent'] > 0 ? " ({$item['weight_percent']}%)" : '');
        }

        $ticket->unsetRelation('problems');
        $ticket->load('problems');
        if ($ticket->hasWeightedProblems()) {
            $ticket->recalculateProgressFromProblems();
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => 'problems_set',
            'note' => 'Supervisor menetapkan ' . $items->count() . ' poin pekerjaan untuk staff: ' . implode('; ', $labels) . '.',
        ]);

        return $items->count();
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
        if ($ticket->hasWeightedProblems()) {
            $ticket->recalculateProgressFromProblems();
        }

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

        $ticket->unsetRelation('problems');
        $ticket->load('problems');

        $weightNote = $problem->weight_percent > 0 ? " ({$problem->weight_percent}%)" : '';
        $newPercent = null;
        if ($ticket->hasWeightedProblems()) {
            $newPercent = $ticket->recalculateProgressFromProblems();
            if ($ticket->status === 'open' && $done) {
                $ticket->update(['status' => 'in_progress']);
            }
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'from_role_id' => $user->role_id,
            'to_role_id' => $ticket->current_role_id,
            'action' => $done ? 'problem_done' : 'problem_reopened',
            'note' => ($done ? 'Masalah selesai: ' : 'Masalah dibuka lagi: ') . $problem->title . $weightNote
                . ($newPercent !== null ? ". Total progres pengerjaan: {$newPercent}%." : '.'),
        ]);

        $flashMessage = $done
            ? ($newPercent !== null ? "Poin pekerjaan selesai (+{$problem->weight_percent}%). Progres tiket naik menjadi {$newPercent}%." : 'Masalah ditandai selesai.')
            : ($newPercent !== null ? "Poin pekerjaan dibuka kembali. Progres tiket menjadi {$newPercent}%." : 'Masalah dibuka lagi.');

        return back()->with('success', $flashMessage);
    }

    public function updateProgress(Request $request, Ticket $ticket)
    {
        $user = Auth::user()->load('role');
        $ticket->load('currentRole');

        if (!$ticket->canReportProgress($user)) {
            return back()->with('error', 'Hanya staff yang ditugaskan yang dapat mengisi persentase pengerjaan.');
        }

        $ticket->load('problems');
        $weighted = $ticket->hasWeightedProblems();
        $validated = $request->validate([
            'progress_percent' => [$weighted ? 'nullable' : 'required', 'integer', 'between:0,100'],
            'note' => ['nullable', 'string', 'max:500'],
            'work_status' => ['nullable', 'in:delivered,in_progress,blocked,finished'],
        ]);

        $note = trim((string) ($validated['note'] ?? ''));
        $workStatus = $validated['work_status'] ?? null;
        if ($workStatus === 'blocked' && $note === '') {
            return back()->with('error', 'Blocked berarti ada masalah saat pengerjaan. Tuliskan masalahnya di catatan.')->withInput();
        }

        if ($weighted) {
            $this->recordReportedProgress($ticket, $user, null, $note, $workStatus);
            $percent = $ticket->recalculateProgressFromProblems();

            return back()->with('success', 'Progres '.($percent ?? 0).'% mengikuti poin pekerjaan yang dicentang.');
        }

        $percent = (int) $validated['progress_percent'];
        $this->recordReportedProgress($ticket, $user, $percent, $note, $workStatus);

        return back()->with('success', 'Progres ' . $percent . '% tercatat. Kepala dapat memantau angka ini.');
    }

    private function recordReportedProgress(Ticket $ticket, User $user, ?int $percent, ?string $note = null, ?string $workStatus = null): void
    {
        $note = $note !== null ? trim($note) : null;
        $storedNote = $note === '' ? null : $note;
        $percentUnchanged = $percent === null || ($ticket->progress_percent !== null && (int) $ticket->progress_percent === $percent);
        $noteUnchanged = $note === null || (string) ($ticket->progress_note ?? '') === (string) ($storedNote ?? '');
        $workUnchanged = $workStatus === null || (string) $ticket->work_status === $workStatus;

        if ($percentUnchanged && $noteUnchanged && $workUnchanged) {
            return;
        }

        $changes = [];
        if ($percent !== null) {
            $changes['progress_percent'] = $percent;
        }
        if ($note !== null) {
            $changes['progress_note'] = $storedNote;
        }
        if ($workStatus !== null) {
            $changes = array_merge($changes, $ticket->workMilestoneChanges($workStatus));
        }
        $ticket->update($changes);

        $logNote = $percent === null
            ? 'Staff memperbarui status pengerjaan.'
            : 'Staff melaporkan progres pengerjaan ' . $percent . '%.';
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
        $staffUsers = $role->assignableStaffUsers();

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

        $assignableStaff = $ticket->currentRole
            ? $ticket->currentRole->assignableStaffUsers()
            : collect();
        $staff = $assignableStaff->firstWhere('id', (int) $chosenStaffId)
            ?? User::whereKey($chosenStaffId)->where('role_id', $ticket->current_role_id)->first();
        if (!$staff) {
            return 'Staff yang dipilih bukan anggota role ini.';
        }

        if ((int) $ticket->priority_rank === $rank && (int) $ticket->assigned_user_id === (int) $staff->id) {
            return null;
        }

        $ticket->loadMissing('currentRole');
        $ticket->update(array_merge([
            'priority_rank' => $rank,
            'priority' => RolePriorityRoute::legacyPriority($rank),
            'assigned_user_id' => $staff->id,
        ], $ticket->deliveryStamp($actor, $staff)));

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
