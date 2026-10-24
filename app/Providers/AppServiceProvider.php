<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            if (!Schema::hasTable('users') || !Schema::hasTable('roles')) {
                return;
            }

            $user = Auth::user();
            if ($user) {
                $user->loadMissing(['role.parent', 'role.children']);
                $accessibleMenus = $user->accessibleMenus();
                $sidebarMenus = $accessibleMenus
                    ->where('show_in_sidebar', true)
                    ->whereNull('parent_id')
                    ->groupBy('group');
                $launcherMenus = $accessibleMenus
                    ->where('show_in_launcher', true)
                    ->whereNull('parent_id');

                $roleInboxCount = Ticket::where('current_role_id', $user->role_id)
                    ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
                    ->count();

                // Data kategori global untuk Modal Buat Tiket di seluruh halaman
                $globalCategories = Schema::hasTable('ticket_categories')
                    ? TicketCategory::with(['initialTargetRole.parent', 'maxApprovalRole'])
                        ->where('is_active', true)
                        ->get()
                    : collect();

                $globalCategoryChains = [];
                foreach ($globalCategories as $cat) {
                    $chain = $cat->getWorkflowChain($user->role);
                    $configuredRoles = $cat->getCustomWorkflowRoles();
                    $fullRoles = $configuredRoles->isNotEmpty()
                        ? $configuredRoles
                        : $cat->getFullHierarchyRoles($user->role);
                    $globalCategoryChains[$cat->id] = [
                        'workflow_type' => $cat->workflow_type,
                        'workflow_label' => $cat->workflow_type === 'tiered_approval'
                            ? 'Approval Berjenjang'
                            : 'Eskalasi / Disposisi',
                        'target_role' => $chain->first()?->name ?? ($user->role?->parent?->name ?? 'Admin'),
                        'chain_names' => $fullRoles->isNotEmpty()
                            ? $fullRoles->pluck('name')->toArray()
                            : $chain->pluck('name')->toArray(),
                        'has_template' => $cat->hasTemplate(),
                        'template_name' => $cat->template_name,
                        'template_preview_url' => $cat->hasTemplate() ? route('categories.template.preview', $cat) : null,
                        'template_download_url' => $cat->hasTemplate() ? route('categories.template.download', $cat) : null,
                        'is_attachment_required' => (bool) $cat->is_attachment_required,
                    ];
                }

                // Notifikasi Header: Tiket Masuk & Proses Tiket Selesai
                $headerNotifications = collect();
                $incomingNotifCount = 0;
                $completedNotifCount = 0;
                $totalNotifCount = 0;

                if (Schema::hasTable('tickets')) {
                    $headId = $user->role?->head_user_id;
                    $isHead = $headId && (int) $headId === (int) $user->id;
                    $isAdminIt = in_array($user->role?->slug, ['admin-it', 'supervisor-it'], true);

                    $incomingQuery = Ticket::with(['creator', 'category'])
                        ->whereNotIn('status', ['resolved', 'rejected', 'closed']);

                    if ($isHead || $user->isSuperuser()) {
                        $incomingQuery->where('current_role_id', $user->role_id);
                    } elseif ($isAdminIt) {
                        $itRoleIds = \App\Models\Role::whereIn('slug', ['admin-it', 'supervisor-it'])->pluck('id')->toArray();
                        $incomingQuery->whereIn('current_role_id', $itRoleIds);
                    } elseif ($headId) {
                        $incomingQuery->where('assigned_user_id', $user->id);
                    } else {
                        $incomingQuery->where(function ($q) use ($user) {
                            $q->where('current_role_id', $user->role_id)
                              ->orWhere('assigned_user_id', $user->id);
                        });
                    }

                    $incomingList = $incomingQuery->latest('updated_at')
                        ->take(8)
                        ->get()
                        ->map(function ($t) {
                            return [
                                'id' => 'inc-' . $t->id,
                                'ticket_id' => $t->id,
                                'type' => 'incoming',
                                'type_label' => 'Tiket Masuk',
                                'ticket_number' => $t->ticket_number,
                                'title' => $t->title,
                                'category_name' => $t->category?->name ?? 'Layanan',
                                'creator_name' => $t->creator?->name ?? 'Pemohon',
                                'time_ago' => $t->created_at?->diffForHumans() ?? 'Baru saja',
                                'raw_time' => $t->updated_at ? $t->updated_at->timestamp : ($t->created_at ? $t->created_at->timestamp : 0),
                                'status_label' => $t->statusLabel(),
                                'priority_label' => $t->priorityLabel(),
                                'detail' => 'Dari: ' . ($t->creator?->name ?? 'Pemohon') . ' · ' . ($t->category?->name ?? 'Layanan'),
                                'url' => route('tickets.show', $t),
                                'is_finished' => false,
                            ];
                        });

                    $completedQuery = Ticket::with(['creator', 'category'])
                        ->where(function ($q) {
                            $q->whereIn('status', ['resolved', 'closed'])
                              ->orWhere('work_status', 'finished');
                        });

                    if ($isHead || $user->isSuperuser()) {
                        $completedQuery->where(function ($q) use ($user) {
                            $q->where('current_role_id', $user->role_id)
                              ->orWhere('creator_id', $user->id);
                        });
                    } elseif ($isAdminIt) {
                        $itRoleIds = \App\Models\Role::whereIn('slug', ['admin-it', 'supervisor-it'])->pluck('id')->toArray();
                        $completedQuery->where(function ($q) use ($itRoleIds, $user) {
                            $q->whereIn('current_role_id', $itRoleIds)
                              ->orWhere('creator_id', $user->id);
                        });
                    } else {
                        $completedQuery->where(function ($q) use ($user) {
                            $q->where('creator_id', $user->id)
                              ->orWhere('assigned_user_id', $user->id);
                        });
                    }

                    $completedList = $completedQuery->latest('updated_at')
                        ->take(8)
                        ->get()
                        ->map(function ($t) {
                            return [
                                'id' => 'comp-' . $t->id,
                                'ticket_id' => $t->id,
                                'type' => 'completed',
                                'type_label' => 'Tiket Selesai',
                                'ticket_number' => $t->ticket_number,
                                'title' => $t->title,
                                'category_name' => $t->category?->name ?? 'Layanan',
                                'creator_name' => $t->creator?->name ?? 'Pemohon',
                                'time_ago' => $t->resolved_at?->diffForHumans() ?? ($t->finished_at?->diffForHumans() ?? $t->updated_at?->diffForHumans()),
                                'raw_time' => $t->resolved_at ? $t->resolved_at->timestamp : ($t->finished_at ? $t->finished_at->timestamp : ($t->updated_at ? $t->updated_at->timestamp : 0)),
                                'status_label' => 'Selesai',
                                'priority_label' => $t->priorityLabel(),
                                'detail' => 'Pengerjaan tuntas · ' . ($t->category?->name ?? 'Layanan'),
                                'url' => route('tickets.show', $t),
                                'is_finished' => true,
                            ];
                        });

                    $headerNotifications = $incomingList->concat($completedList)->sortByDesc('raw_time')->values();
                    $incomingNotifCount = $incomingList->count();
                    $completedNotifCount = $completedList->count();
                    $totalNotifCount = $headerNotifications->count();
                }

                $view->with([
                    'authUser' => $user,
                    'sidebarMenus' => $sidebarMenus,
                    'launcherMenus' => $launcherMenus,
                    'roleInboxCount' => $roleInboxCount,
                    'globalCategories' => $globalCategories,
                    'globalCategoryChains' => $globalCategoryChains,
                    'headerNotifications' => $headerNotifications,
                    'incomingNotifCount' => $incomingNotifCount,
                    'completedNotifCount' => $completedNotifCount,
                    'totalNotifCount' => $totalNotifCount,
                ]);
            }
        });
    }
}
