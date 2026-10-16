<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketingHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_superuser_can_create_new_role_and_assign_hierarchy_to_dosen_and_kaprodi(): void
    {
        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $dosenRole = Role::where('slug', 'dosen')->firstOrFail();
        $kaprodiRole = Role::where('slug', 'kaprodi')->firstOrFail();

        $this->assertEquals($kaprodiRole->id, $dosenRole->parent_id);

        $response = $this->actingAs($superuser)->post(route('admin.hierarchy.store'), [
            'name' => 'Asisten Dosen',
            'parent_id' => $dosenRole->id,
            'badge_color' => 'indigo',
            'description' => 'Membantu praktikum dan meneruskan tiket ke Dosen.',
            'can_create_ticket' => 1,
            'can_receive_ticket' => 1,
        ]);

        $response->assertRedirect();
        $newRole = Role::where('name', 'Asisten Dosen')->first();
        $this->assertNotNull($newRole);
        $this->assertEquals($dosenRole->id, $newRole->parent_id);
        $this->assertEquals(['Asisten Dosen', 'Dosen', 'Kaprodi', 'Dekan'], $newRole->getUpwardChainNames());
    }

    public function test_mahasiswa_creates_ticket_with_optional_document_and_dosen_sets_priority_and_escalates(): void
    {
        Storage::fake('public');

        $mahasiswa = User::where('email', 'mahasiswa@kampus.ac.id')->firstOrFail();
        $dosen = User::where('email', 'dosen@kampus.ac.id')->firstOrFail();
        $kaprodi = User::where('email', 'kaprodi@kampus.ac.id')->firstOrFail();
        $category = TicketCategory::where('workflow_type', 'escalation')->firstOrFail();

        $file = UploadedFile::fake()->create('surat_permohonan.pdf', 200, 'application/pdf');

        // 1. Mahasiswa membuat tiket (tanpa prioritas, dengan dokumen opsional)
        $this->actingAs($mahasiswa)->post(route('tickets.store'), [
            'title' => 'Pengajuan Penambahan Kuota KRS',
            'category_id' => $category->id,
            'description' => 'Mohon bantuan pembukaan kuota KRS untuk mata kuliah pilihan.',
            'attachment' => $file,
        ])->assertRedirect();

        $ticket = Ticket::where('title', 'Pengajuan Penambahan Kuota KRS')->firstOrFail();
        $this->assertNull($ticket->priority);
        $this->assertEquals('surat_permohonan.pdf', $ticket->attachment_name);
        $this->assertNotNull($ticket->attachment_path);
        $this->assertEquals($dosen->role_id, $ticket->current_role_id);

        // Pastikan dokumen dapat di-preview secara inline maupun diunduh
        $this->actingAs($dosen)->get(route('tickets.preview', $ticket))->assertOk();
        $this->actingAs($dosen)->get(route('tickets.attachment', $ticket))->assertOk();

        // 2. Dosen mencari & memfilter tiket di Tiket Masuk berdasarkan kategori, lalu mengatur Prioritas
        $this->actingAs($dosen)->get(route('tickets.inbox', [
            'search' => 'Kuota KRS',
            'category_id' => $category->id,
        ]))->assertOk()->assertSee('Pengajuan Penambahan Kuota KRS');

        $this->actingAs($dosen)->post(route('tickets.priority', $ticket), [
            'priority' => 'high',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('high', $ticket->priority);

        // 3. Dosen melakukan eskalasi ke Kaprodi
        $this->actingAs($dosen)->post(route('tickets.action', $ticket), [
            'action_type' => 'escalate',
            'priority' => 'urgent',
            'note' => 'Mohon kebijakan Ibu Kaprodi untuk penambahan kuota kelas.',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals($kaprodi->role_id, $ticket->current_role_id);
        $this->assertEquals('urgent', $ticket->priority);
        $this->assertEquals('escalated', $ticket->status);

        // Saat masih dieskalasi (sedang diproses), tiket tampil di menu "Tiket Saya" beserta tombol "+ Buat Tiket Baru"
        $this->actingAs($mahasiswa)->get(route('tickets.my'))
            ->assertOk()
            ->assertSee('+ Buat Tiket Baru')
            ->assertSee('Pengajuan Penambahan Kuota KRS');

        // Dan belum tampil di menu "Riwayat Tiket" (karena belum berstatus approve/resolved)
        $this->actingAs($mahasiswa)->get(route('tickets.history'))
            ->assertOk()
            ->assertDontSee('Pengajuan Penambahan Kuota KRS');

        // 4. Kaprodi menyelesaikan/menyetujui tiket, lalu Mahasiswa melihat di menu "Riwayat Tiket" (fokus yang berstatus Approve saja)
        $this->actingAs($kaprodi)->post(route('tickets.action', $ticket), [
            'action_type' => 'resolve',
            'note' => 'Disetujui penambahan kuota.',
        ])->assertRedirect();

        $this->actingAs($mahasiswa)->get(route('tickets.history'))
            ->assertOk()
            ->assertSee('Pengajuan Penambahan Kuota KRS')
            ->assertSee('Preview Dokumen');
    }

    public function test_all_main_pages_render_successfully_across_roles(): void
    {
        $users = User::all();
        foreach ($users as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
            $this->actingAs($user)->get(route('tickets.inbox'))->assertOk();
            $this->actingAs($user)->get(route('tickets.my'))->assertOk();
            $this->actingAs($user)->get(route('tickets.history'))->assertOk();
            $this->actingAs($user)->get(route('tickets.create'))->assertRedirect(route('tickets.my'));
        }

        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $this->actingAs($superuser)->get(route('admin.hierarchy'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.menus'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.categories'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.users'))->assertRedirect(route('admin.users.students'));
        $this->actingAs($superuser)->get(route('admin.users.students'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.users.lecturers'))->assertOk();
    }

    public function test_siakad_rest_api_syncs_students_and_lecturers_with_roles(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/api/external/lecturers*' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'data' => [
                    [
                        'id_lecture' => 64,
                        'name' => 'Dwi Utari Iswavigra, S.T., M.Kom.',
                        'nidn' => '0626049701',
                        'email' => 'dwi.utari@sugenghartono.ac.id',
                        'program_studi' => 'Informatika',
                        'fakultas' => 'FAKULTAS TEKNOLOGI, HUKUM DAN BISNIS',
                        'jabatan' => 'Kaprodi Informatika',
                        'role_slug' => 'kaprodi',
                        'students_count' => 15,
                    ],
                    [
                        'id_lecture' => 71,
                        'name' => 'Yulaikha Maratullatifah, S.Pd., M.Kom.',
                        'nidn' => '0630079202',
                        'email' => 'yulaikha@sugenghartono.ac.id',
                        'program_studi' => 'Informatika',
                        'fakultas' => 'FAKULTAS TEKNOLOGI, HUKUM DAN BISNIS',
                        'jabatan' => 'Dosen Tetap',
                        'role_slug' => 'dosen',
                        'students_count' => 10,
                    ],
                ],
            ], 200),
            '*/api/external/students*' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'data' => [
                    [
                        'id_mahasiswa' => 109,
                        'nim' => '062201002',
                        'name' => 'WIBOWO JAMAL NUGROHO',
                        'email' => 'wibowo@mhs.sugenghartono.ac.id',
                        'angkatan' => 2022,
                        'semester' => 8,
                        'program_studi' => 'Informatika',
                        'fakultas' => 'FAKULTAS TEKNOLOGI, HUKUM DAN BISNIS',
                        'status_mahasiswa' => 'Aktif',
                        'id_dosen_pembimbing' => 71,
                    ],
                ],
            ], 200),
        ]);

        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();

        $this->actingAs($superuser)
            ->post(route('admin.users.sync-siakad'), ['type' => 'all'])
            ->assertRedirect();

        $syncedKaprodi = User::where('email', 'dwi.utari@sugenghartono.ac.id')->firstOrFail();
        $this->assertEquals('kaprodi', $syncedKaprodi->role->slug);

        $syncedDosen = User::where('email', 'yulaikha@sugenghartono.ac.id')->firstOrFail();
        $this->assertEquals('dosen', $syncedDosen->role->slug);

        $syncedStudent = User::where('identity_number', '062201002')->firstOrFail();
        $this->assertEquals('mahasiswa', $syncedStudent->role->slug);
        $this->assertEquals($syncedDosen->id, $syncedStudent->supervisor_id);
    }
}
