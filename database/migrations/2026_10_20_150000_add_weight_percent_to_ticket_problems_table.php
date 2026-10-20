<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_problems', function (Blueprint $table) {
            $table->unsignedTinyInteger('weight_percent')->default(0)->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_problems', function (Blueprint $table) {
            $table->dropColumn('weight_percent');
        });
    }
};
