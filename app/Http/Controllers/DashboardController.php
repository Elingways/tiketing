<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load(['role.parent', 'role.children', 'supervisor']);
        $role = $user->role;

        // Tiket Masuk ke Role saat ini (Contoh: Tiket dari Mahasiswa yang masuk ke dashboard Dosen, atau dari Dosen ke Kaprodi)
        $incomingTickets = Ticket::with(['creator', 'creatorRole', 'category', 'currentRole'])
            ->where('current_role_id', $role?->id)
            ->latest()
            ->take(8)
            ->get();

        $activeIncomingCount = Ticket::where('current_role_id', $role?->id)
            ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
            ->count();

        $escalatedFromMyRoleCount = Ticket::whereHas('logs', function ($q) use ($role) {
            $q->where('from_role_id', $role?->id)
                ->whereIn('action', ['escalated', 'approved_step']);
        })->count();

        // Tiket yang dibuat oleh user ini sendiri (Misal Mahasiswa melihat progres tiketnya)
        $myCreatedTickets = Ticket::with(['category', 'currentRole', 'assignedUser'])
            ->where('creator_id', $user->id)
            ->latest()
            ->take(6)
            ->get();

        // Statistik Global untuk Superuser & Statistik Role untuk civitas
        $stats = [
            'role_inbox_active' => $activeIncomingCount,
            'my_tickets_total' => Ticket::where('creator_id', $user->id)->count(),
            'my_tickets_resolved' => Ticket::where('creator_id', $user->id)->where('status', 'resolved')->count(),
            'escalated_or_forwarded' => $escalatedFromMyRoleCount,
            'total_all_tickets' => Ticket::count(),
            'total_roles' => Role::count(),
            'total_menus' => Menu::count(),
            'total_users' => User::count(),
        ];

        // Pohon Hirarki untuk preview visual di dashboard
        $rootRoles = Role::with('childrenRecursive')
            ->whereNull('parent_id')
            ->where('is_superuser', false)
            ->get();

        $categories = TicketCategory::with(['initialTargetRole', 'maxApprovalRole'])
            ->where('is_active', true)
            ->get();

        return view('dashboard', compact(
            'user',
            'role',
            'incomingTickets',
            'myCreatedTickets',
            'stats',
            'rootRoles',
            'categories'
        ));
    }
}
