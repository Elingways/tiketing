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
                $sidebarMenus = $accessibleMenus->where('show_in_sidebar', true)->groupBy('group');
                $launcherMenus = $accessibleMenus->where('show_in_launcher', true);

                $roleInboxCount = Ticket::where('current_role_id', $user->role_id)
                    ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
                    ->count();

                $allDemoUsers = User::with('role')->orderBy('role_id')->get();

                // Data kategori global untuk Modal Buat Tiket di seluruh halaman
                $globalCategories = Schema::hasTable('ticket_categories')
                    ? TicketCategory::with(['initialTargetRole.parent', 'maxApprovalRole'])
                        ->where('is_active', true)
                        ->get()
                    : collect();

                $globalCategoryChains = [];
                foreach ($globalCategories as $cat) {
                    $chain = $cat->getWorkflowChain($user->role);
                    $globalCategoryChains[$cat->id] = [
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

                $view->with([
                    'authUser' => $user,
                    'sidebarMenus' => $sidebarMenus,
                    'launcherMenus' => $launcherMenus,
                    'roleInboxCount' => $roleInboxCount,
                    'allDemoUsers' => $allDemoUsers,
                    'globalCategories' => $globalCategories,
                    'globalCategoryChains' => $globalCategoryChains,
                ]);
            }
        });
    }
}
