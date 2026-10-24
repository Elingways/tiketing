<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('ticket');
            $table->string('color')->default('indigo');
            $table->text('description')->nullable();
            $table->json('workflow_roles')->nullable();
            $table->foreignId('initial_target_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->enum('workflow_type', ['escalation', 'tiered_approval'])->default('tiered_approval');
            $table->foreignId('max_approval_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('template_path')->nullable();
            $table->string('template_name')->nullable();
            $table->boolean('is_attachment_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
