<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('divisions')) {
            Schema::create('divisions', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('name');
                $table->string('slug', 100)->unique();
                $table->string('badge_color')->default('indigo');
                $table->string('description')->nullable();
                $table->foreignId('head_role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('spv_role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('staff_role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();
            });
        } elseif (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE divisions ENGINE=InnoDB');
            $this->addMysqlForeignKey(
                'divisions',
                'divisions_head_role_id_fk',
                'ALTER TABLE divisions ADD CONSTRAINT divisions_head_role_id_fk FOREIGN KEY (head_role_id) REFERENCES roles (id) ON DELETE CASCADE'
            );
            $this->addMysqlForeignKey(
                'divisions',
                'divisions_spv_role_id_fk',
                'ALTER TABLE divisions ADD CONSTRAINT divisions_spv_role_id_fk FOREIGN KEY (spv_role_id) REFERENCES roles (id) ON DELETE CASCADE'
            );
            $this->addMysqlForeignKey(
                'divisions',
                'divisions_staff_role_id_fk',
                'ALTER TABLE divisions ADD CONSTRAINT divisions_staff_role_id_fk FOREIGN KEY (staff_role_id) REFERENCES roles (id) ON DELETE CASCADE'
            );
        }

        if (!Schema::hasColumn('ticket_categories', 'division_id')) {
            Schema::table('ticket_categories', function (Blueprint $table) {
                $table->foreignId('division_id')->nullable()->after('workflow_type')->constrained('divisions')->nullOnDelete();
            });
        } elseif (Schema::getConnection()->getDriverName() === 'mysql') {
            $this->addMysqlForeignKey(
                'ticket_categories',
                'ticket_categories_division_id_foreign',
                'ALTER TABLE ticket_categories ADD CONSTRAINT ticket_categories_division_id_foreign FOREIGN KEY (division_id) REFERENCES divisions (id) ON DELETE SET NULL'
            );
        }

        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('slug', 'admin-roles')->update([
                'name' => 'Manajemen Divisi',
                'description' => 'Susunan divisi: Kepala, SPV, lalu staff. Puncak divisi dipakai layanan.',
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ticket_categories', 'division_id')) {
            Schema::table('ticket_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('division_id');
            });
        }

        Schema::dropIfExists('divisions');
    }

    private function addMysqlForeignKey(string $table, string $constraint, string $statement): void
    {
        $exists = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $constraint]
        );

        if ($exists === []) {
            DB::statement($statement);
        }
    }
};
