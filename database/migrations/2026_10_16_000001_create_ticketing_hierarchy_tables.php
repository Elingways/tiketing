<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Roles dengan Self-Referencing Hierarchy (parent_id)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->integer('level')->default(0);
            $table->string('badge_color')->default('indigo');
            $table->string('icon')->default('shield');
            $table->text('description')->nullable();
            $table->boolean('is_superuser')->default(false);
            $table->boolean('can_create_ticket')->default(true);
            $table->boolean('can_receive_ticket')->default(true);
            $table->timestamps();
        });

        // 2. Tambahkan relasi role & atribut akademik ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('role_id')->constrained('users')->nullOnDelete();
            $table->string('identity_number')->nullable()->after('email'); // NIM / NIDN / NIP
            $table->string('department')->nullable()->after('identity_number'); // Prodi / Unit
            $table->string('avatar_color')->default('indigo')->after('department');
        });

        // 3. Tabel Menus (Manajemen Menu Dinamis SuperApp)
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('route_name');
            $table->string('icon')->default('grid');
            $table->string('color')->default('indigo');
            $table->string('group')->default('main'); // main, ticketing, management
            $table->string('badge_text')->nullable();
            $table->text('description')->nullable();
            $table->boolean('show_in_sidebar')->default(true);
            $table->boolean('show_in_launcher')->default(true); // Tampil di Grid SuperApp
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 4. Pivot Menu <-> Role (Hak Akses Menu Dinamis per Role)
        Schema::create('menu_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->unique(['menu_id', 'role_id']);
        });

        // 5. Tabel Kategori Tiket & Konfigurasi Alur Hirarki (Eskalasi vs Approval Berjenjang)
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('ticket');
            $table->string('color')->default('indigo');
            $table->text('description')->nullable();
            // Role penerima awal (jika kosong, otomatis mengikuti parent_id dari role pembuat tiket)
            $table->foreignId('initial_target_role_id')->nullable()->constrained('roles')->nullOnDelete();
            // Mode workflow: 'escalation' (Disposisi/Eskalasi manual ke atasan) atau 'tiered_approval' (Approval otomatis berjenjang naik ke atasan)
            $table->enum('workflow_type', ['escalation', 'tiered_approval'])->default('escalation');
            // Batas puncak role approval untuk mode tiered_approval (misal sampai Kaprodi atau Dekan)
            $table->foreignId('max_approval_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Tabel Tickets
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->foreignId('category_id')->constrained('ticket_categories')->cascadeOnDelete();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->nullable();
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
            $table->integer('approval_step')->default(1);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 7. Tabel Ticket Logs (Timeline Riwayat Hirarki, Eskalasi, Approval & Diskusi)
        Schema::create('ticket_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('to_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('action'); // created, comment, in_progress, escalated, approved_step, final_approved, rejected, resolved, disposed
            $table->text('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_logs');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('menu_role');
        Schema::dropIfExists('menus');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['identity_number', 'department', 'avatar_color']);
        });
        Schema::dropIfExists('roles');
    }
};
