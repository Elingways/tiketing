<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets') || Schema::hasColumn('tickets', 'achievement_points')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedSmallInteger('achievement_points')->nullable()->after('finished_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('tickets', 'achievement_points')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('achievement_points');
        });
    }
};
