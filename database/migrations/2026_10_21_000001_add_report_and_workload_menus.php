<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menus') || !Schema::hasTable('roles') || !Schema::hasTable('menu_role')) {
            return;
        }

        $roleIds = DB::table('roles')->pluck('id');
        $now = now();
        $menus = [
            [
                'name' => 'Laporan Bulanan',
                'slug' => 'tickets-report',
                'route_name' => 'tickets.report',
                'icon' => 'document-check',
                'color' => 'sky',
                'group' => 'ticketing',
                'badge_text' => 'Kepala Role',
                'description' => 'Performa staff, progres, dan status pengerjaan pada bulan yang dipilih.',
                'show_in_sidebar' => true,
                'show_in_launcher' => false,
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Pekerjaan Divisi',
                'slug' => 'tickets-workload',
                'route_name' => 'tickets.workload',
                'icon' => 'user-group',
                'color' => 'indigo',
                'group' => 'ticketing',
                'badge_text' => 'Sedang dikerjakan',
                'description' => 'Pekerjaan yang sedang dikerjakan IT dan setiap divisi.',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'is_active' => true,
                'sort_order' => 9,
            ],
        ];

        foreach ($menus as $menu) {
            $existingId = DB::table('menus')->where('slug', $menu['slug'])->value('id');
            if ($existingId) {
                DB::table('menus')->where('id', $existingId)->update(array_merge($menu, ['updated_at' => $now]));
                $menuId = $existingId;
            } else {
                $menuId = DB::table('menus')->insertGetId(array_merge($menu, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }

            foreach ($roleIds as $roleId) {
                $linked = DB::table('menu_role')
                    ->where('menu_id', $menuId)
                    ->where('role_id', $roleId)
                    ->exists();
                if (!$linked) {
                    DB::table('menu_role')->insert([
                        'menu_id' => $menuId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        $ids = DB::table('menus')->whereIn('slug', ['tickets-report', 'tickets-workload'])->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('menu_role')) {
            DB::table('menu_role')->whereIn('menu_id', $ids)->delete();
        }
        DB::table('menus')->whereIn('id', $ids)->delete();
    }
};
