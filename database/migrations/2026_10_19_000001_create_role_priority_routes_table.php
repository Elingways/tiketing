<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_priority_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->unsignedTinyInteger('priority_level');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'priority_level']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority_rank')->nullable()->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('priority_rank');
        });

        Schema::dropIfExists('role_priority_routes');
    }
};
