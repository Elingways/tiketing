<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('level')->default(0);
            $table->string('badge_color')->default('indigo');
            $table->string('icon')->default('shield');
            $table->text('description')->nullable();
            $table->boolean('is_superuser')->default(false);
            $table->boolean('can_create_ticket')->default(true);
            $table->boolean('can_receive_ticket')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
