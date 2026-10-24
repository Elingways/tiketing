<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('role_id')->constrained('users')->nullOnDelete();
            $table->string('user_type')->default('mahasiswa')->after('supervisor_id'); // mahasiswa, pengajar, admin
            $table->unsignedBigInteger('siakad_id')->nullable()->after('user_type');
            $table->string('identity_number')->nullable()->after('email'); // NIM / NIDN / NIP
            $table->string('department')->nullable()->after('identity_number'); // Program Studi / Unit
            $table->string('fakultas')->nullable()->after('department');
            $table->integer('angkatan')->nullable()->after('fakultas');
            $table->integer('semester')->nullable()->after('angkatan');
            $table->string('status_akademik')->default('Aktif')->after('semester');
            $table->string('jabatan')->nullable()->after('status_akademik');
            $table->string('keahlian')->nullable()->after('jabatan');
            $table->string('phone')->nullable()->after('keahlian');
            $table->string('avatar_color')->default('indigo')->after('phone');
            $table->timestamp('synced_at')->nullable()->after('avatar_color');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn([
                'user_type',
                'siakad_id',
                'identity_number',
                'department',
                'fakultas',
                'angkatan',
                'semester',
                'status_akademik',
                'jabatan',
                'keahlian',
                'phone',
                'avatar_color',
                'synced_at',
            ]);
        });
    }
};
