<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->foreignId('head_role_id')->nullable()->change();
            $table->foreignId('spv_role_id')->nullable()->change();
            $table->foreignId('staff_role_id')->nullable()->change();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('parent_id')->constrained('divisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
        });

        Schema::table('divisions', function (Blueprint $table) {
            $table->foreignId('head_role_id')->nullable(false)->change();
            $table->foreignId('spv_role_id')->nullable(false)->change();
            $table->foreignId('staff_role_id')->nullable(false)->change();
        });
    }
};
