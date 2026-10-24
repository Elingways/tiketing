<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->foreignId('category_id')->constrained('ticket_categories')->cascadeOnDelete();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->nullable();
            $table->unsignedTinyInteger('priority_rank')->nullable();
            $table->unsignedTinyInteger('assessed_priority_rank')->nullable();
            $table->enum('workflow_type', ['escalation', 'tiered_approval'])->default('escalation');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creator_role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('current_role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'open',
                'in_progress',
                'escalated',
                'waiting_next_approval',
                'resolved',
                'rejected',
                'closed',
            ])->default('open');
            $table->unsignedTinyInteger('progress_percent')->nullable();
            $table->string('progress_note', 500)->nullable();
            $table->string('work_status', 20)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('approval_step')->default(1);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
