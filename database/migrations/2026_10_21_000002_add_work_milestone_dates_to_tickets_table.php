<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('work_status');
            $table->timestamp('finished_at')->nullable()->after('delivered_at');
        });

        DB::table('tickets')
            ->whereNull('delivered_at')
            ->whereNotNull('work_status')
            ->update(['delivered_at' => DB::raw('created_at')]);

        DB::table('tickets')
            ->whereNull('finished_at')
            ->where('work_status', 'finished')
            ->update(['finished_at' => DB::raw('COALESCE(resolved_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'finished_at']);
        });
    }
};
