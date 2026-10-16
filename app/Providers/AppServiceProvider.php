<?php

namespace App\Providers;

use App\Models\Ticket;
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

                $view->with([
                    'authUser' => $user,
                    'sidebarMenus' => $sidebarMenus,
                    'launcherMenus' => $launcherMenus,
                    'roleInboxCount' => $roleInboxCount,
                    'allDemoUsers' => $allDemoUsers,
                ]);
            }
        });
    }
}
