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
            'priority_rank' => 2,
            'attachment' => $file,
        ])->assertRedirect();

        $ticket = Ticket::where('title', 'Pengajuan Penambahan Kuota KRS')->firstOrFail();
        $this->assertSame(2, (int) $ticket->priority_rank);
        $this->assertSame('high', $ticket->priority);
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
        $this->actingAs($superuser)->get(route('admin.hierarchy'))->assertRedirect(route('admin.categories'));
        $this->actingAs($superuser)->get(route('admin.menus'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.categories'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.users'))->assertRedirect(route('admin.users.students'));
        $this->actingAs($superuser)->get(route('admin.users.students'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.users.lecturers'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.users.employees'))->assertOk();
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

    public function test_superuser_creates_service_category_with_custom_hierarchy_and_template_document(): void
    {
        Storage::fake('public');

        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $mahasiswa = User::where('email', 'mahasiswa@kampus.ac.id')->firstOrFail();
        $akademik = User::where('email', 'akademik@kampus.ac.id')->firstOrFail();
        $yayasan = User::where('email', 'yayasan@kampus.ac.id')->firstOrFail();

        $mahasiswaRole = Role::where('slug', 'mahasiswa')->firstOrFail();
        $akademikRole = Role::where('slug', 'akademik')->firstOrFail();
        $yayasanRole = Role::where('slug', 'yayasan')->firstOrFail();

        $templateFile = UploadedFile::fake()->create('Template_Pengadaan_Dana_Ormawa.pdf', 150, 'application/pdf');

        // 1. Superuser membuat Kategori Layanan "Pengadaan Dana Ormawa" dengan hirarki: Mahasiswa -> Akademik -> Yayasan + Template Dokumen
        $this->actingAs($superuser)->post(route('admin.categories.store'), [
            'name' => 'Pengadaan Dana Ormawa',
            'workflow_type' => 'tiered_approval',
            'description' => 'Pengajuan dana kegiatan organisasi mahasiswa melalui Akademik dan Yayasan.',
            'workflow_roles' => [$mahasiswaRole->id, $akademikRole->id, $yayasanRole->id],
            'template_file' => $templateFile,
            'is_attachment_required' => 1,
        ])->assertRedirect();

        $category = TicketCategory::where('name', 'Pengadaan Dana Ormawa')->firstOrFail();
        $this->assertTrue($category->hasTemplate());
        $this->assertEquals('Template_Pengadaan_Dana_Ormawa.pdf', $category->template_name);
        $this->assertTrue($category->is_attachment_required);
        $this->assertEquals(
            ['Mahasiswa', 'Akademik', 'Yayasan'],
            $category->getFullHierarchyRoles()->pluck('name')->all()
        );

        // 2. Mahasiswa dapat preview & mengunduh template dokumen layanan
        $this->actingAs($mahasiswa)->get(route('categories.template.preview', $category))->assertOk();
        $this->actingAs($mahasiswa)->get(route('categories.template.download', $category))->assertOk();

        // 3. Mahasiswa mengajukan tiket pada layanan "Pengadaan Dana Ormawa" dengan mengunggah dokumen yang sudah diisi
        $filledDoc = UploadedFile::fake()->create('Form_Pengadaan_Dana_Terisi.pdf', 220, 'application/pdf');

        $this->actingAs($mahasiswa)->post(route('tickets.store'), [
            'title' => 'Pengajuan Dana Seminar Nasional AI',
            'category_id' => $category->id,
            'description' => 'Mohon persetujuan pencairan dana kegiatan seminar nasional.',
            'priority_rank' => 1,
            'attachment' => $filledDoc,
        ])->assertRedirect();

        $ticket = Ticket::where('title', 'Pengajuan Dana Seminar Nasional AI')->firstOrFail();
        // Tahap pertama setelah Mahasiswa adalah Akademik
        $this->assertEquals($akademikRole->id, $ticket->current_role_id);

        // 4. Role Akademik menyetujui tahap 1 -> otomatis naik ke Role Yayasan
        $this->actingAs($akademik)->post(route('tickets.action', $ticket), [
            'action_type' => 'approve_step',
            'priority' => 'high',
            'note' => 'Proposal dan RAB telah diverifikasi oleh bagian Akademik, diteruskan ke Yayasan.',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals($yayasanRole->id, $ticket->current_role_id);
        $this->assertEquals('waiting_next_approval', $ticket->status);

        // 5. Role Yayasan menyetujui tahap akhir -> status tiket menjadi resolved (Approve)
        $this->actingAs($yayasan)->post(route('tickets.action', $ticket), [
            'action_type' => 'approve_step',
            'note' => 'Pengadaan dana disetujui oleh Yayasan.',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status);
    }

    public function test_superuser_can_create_and_edit_flexible_menu_and_sub_sidebar(): void
    {
        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $parentMenu = \App\Models\Menu::where('slug', 'admin-users')->firstOrFail();
        $superuserRole = Role::where('slug', 'superuser')->firstOrFail();

        $this->actingAs($superuser)->post(route('admin.menus.store'), [
            'name' => 'Direktori Tendik',
            'parent_id' => $parentMenu->id,
            'route_name' => '/admin/users/lecturers',
            'group' => 'management',
            'icon' => 'user-group',
            'sort_order' => 3,
            'show_in_sidebar' => 1,
            'is_active' => 1,
            'roles' => [$superuserRole->id],
        ])->assertRedirect();

        $subMenu = \App\Models\Menu::where('name', 'Direktori Tendik')->firstOrFail();
        $this->assertEquals($parentMenu->id, $subMenu->parent_id);
        $this->assertEquals('/admin/users/lecturers', $subMenu->resolveUrl());

        $this->actingAs($superuser)->put(route('admin.menus.update', $subMenu), [
            'name' => 'Direktori Staf & Pengajar',
            'parent_id' => $parentMenu->id,
            'route_name' => 'admin.users.lecturers',
            'group' => 'management',
            'icon' => 'users',
            'sort_order' => 4,
            'show_in_sidebar' => 1,
            'is_active' => 1,
            'roles' => [$superuserRole->id],
        ])->assertRedirect();

        $subMenu->refresh();
        $this->assertEquals('Direktori Staf & Pengajar', $subMenu->name);
        $this->assertEquals(4, $subMenu->sort_order);
    }

    public function test_admin_it_role_routes_priority_number_to_the_chosen_staff(): void
    {
        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $mahasiswa = User::where('email', 'mahasiswa@kampus.ac.id')->firstOrFail();
        $category = TicketCategory::firstOrFail();

        $this->actingAs($superuser)->post(route('admin.hierarchy.store'), [
            'name' => 'Admin IT',
            'badge_color' => 'indigo',
            'description' => 'Menangani tiket perangkat dan jaringan.',
            'can_create_ticket' => 1,
            'can_receive_ticket' => 1,
            'from_role_modal' => 1,
            'head_index' => 0,
            'staff' => [
                ['name' => 'Kepala IT', 'email' => 'kepala.it@kampus.ac.id', 'keahlian' => 'Koordinasi'],
                ['name' => 'Staff Jaringan', 'email' => 'staff.jaringan@kampus.ac.id', 'keahlian' => 'Jaringan'],
                ['name' => 'Staff Hardware', 'email' => 'staff.hardware@kampus.ac.id', 'keahlian' => 'Perangkat keras'],
            ],
        ])->assertRedirect();

        $role = Role::where('name', 'Admin IT')->latest('id')->firstOrFail();
        $head = User::where('email', 'kepala.it@kampus.ac.id')->firstOrFail();
        $network = User::where('email', 'staff.jaringan@kampus.ac.id')->firstOrFail();
        $hardware = User::where('email', 'staff.hardware@kampus.ac.id')->firstOrFail();

        $this->assertEquals($head->id, $role->head_user_id);
        $this->assertEquals('Jaringan', $network->keahlian);
        $this->assertEquals('Perangkat keras', $hardware->keahlian);

        $this->actingAs($superuser)->get(route('admin.categories'))
            ->assertOk();

        $this->actingAs($superuser)->get(route('admin.roles'))
            ->assertOk()
            ->assertSee('Kepala IT')
            ->assertSee('Perangkat keras');

        $ticket = Ticket::create([
            'ticket_number' => 'TKT-TEST-9001',
            'title' => 'Laptop lab tidak menyala',
            'description' => 'Mohon dicek perangkat lab informatika.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $mahasiswa->id,
            'creator_role_id' => $mahasiswa->role_id,
            'current_role_id' => $role->id,
            'status' => 'open',
        ]);

        $this->actingAs($network)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertDontSee('Laptop lab tidak menyala');

        $this->actingAs($head)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Laptop lab tidak menyala')
            ->assertSee('Perangkat keras');

        $this->actingAs($head)->post(route('tickets.priority', $ticket), [
            'priority_rank' => 1,
        ])->assertRedirect()->assertSessionHas('error');

        $ticket->refresh();
        $this->assertNull($ticket->priority_rank);
        $this->assertNotEquals($hardware->id, $ticket->assigned_user_id);

        $this->travel(2)->minutes();
        $this->actingAs($head)->post(route('tickets.priority', $ticket), [
            'priority_rank' => 1,
            'assigned_user_id' => $hardware->id,
        ])->assertRedirect()->assertSessionHas('success');

        $ticket->refresh();
        $this->assertEquals(1, $ticket->priority_rank);
        $this->assertEquals($hardware->id, $ticket->assigned_user_id);
        $this->assertNotNull($ticket->delivered_at);
        $this->assertTrue($ticket->delivered_at->greaterThan($ticket->created_at));
        $this->assertSame('delivered', $ticket->work_status);

        $deliveredAt = $ticket->delivered_at->copy();
        $this->actingAs($hardware)->post(route('tickets.progress', $ticket), [
            'work_status' => 'in_progress',
            'progress_percent' => 10,
            'progress_note' => 'Mulai cek perangkat.',
        ])->assertRedirect();
        $ticket->refresh();
        $this->assertEquals($deliveredAt->format('Y-m-d H:i:s'), $ticket->delivered_at->format('Y-m-d H:i:s'));

        $this->actingAs($hardware)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Laptop lab tidak menyala')
            ->assertSee('Request date')
            ->assertSee('Delivered date');

        $this->actingAs($network)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertDontSee('Laptop lab tidak menyala');

        $this->actingAs($head)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Request')
            ->assertSee('Delivered')
            ->assertSee('Arahkan ke staff')
            ->assertSee('Sesuai, proses dan bagikan ke staff')
            ->assertSee('Tidak sesuai, tolak')
            ->assertSee('Perangkat keras');

        $mismatch = Ticket::create([
            'ticket_number' => 'TKT-TEST-9002',
            'title' => 'Cek printer ruang dosen',
            'description' => 'Printer hanya macet, bukan gangguan mendesak.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $mahasiswa->id,
            'creator_role_id' => $mahasiswa->role_id,
            'current_role_id' => $role->id,
            'priority_rank' => 1,
            'priority' => 'urgent',
            'status' => 'open',
        ]);

        $this->actingAs($head)->post(route('tickets.action', $mismatch), [
            'action_type' => 'reject',
            'priority_rank' => 1,
            'note' => 'Isinya pengecekan printer, bukan level urgent.',
        ])->assertRedirect()->assertSessionHas('success');

        $mismatch->refresh();
        $this->assertSame('rejected', $mismatch->status);
        $this->assertSame(1, (int) $mismatch->priority_rank);
    }

    public function test_intermediate_role_only_approves_and_forwards_upward(): void
    {
        $kaprodi = User::where('email', 'kaprodi@kampus.ac.id')->firstOrFail();
        $akademikUser = User::where('email', 'akademik@kampus.ac.id')->firstOrFail();
        $akademik = Role::where('name', 'Akademik')->firstOrFail();
        $yayasan = Role::where('name', 'Yayasan')->firstOrFail();

        $category = TicketCategory::create([
            'name' => 'Layanan Lewat Persetujuan',
            'slug' => 'layanan-lewat-persetujuan',
            'workflow_type' => 'escalation',
            'workflow_roles' => [$akademik->id, $yayasan->id],
            'initial_target_role_id' => $akademik->id,
            'is_active' => true,
            'is_attachment_required' => false,
        ]);

        $this->actingAs($kaprodi)->post(route('tickets.store'), [
            'title' => 'Permintaan dari Kaprodi',
            'category_id' => $category->id,
            'description' => 'Akademik hanya menyetujui, lalu tiket naik.',
            'priority_rank' => 2,
        ])->assertRedirect();

        $ticket = Ticket::where('title', 'Permintaan dari Kaprodi')->firstOrFail();
        $this->assertSame($akademik->id, $ticket->current_role_id);

        $this->actingAs($akademikUser)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Setuju, lanjut ke '.$yayasan->name)
            ->assertDontSee('Tandai Sedang Diproses')
            ->assertDontSee('Selesaikan Tiket')
            ->assertDontSee('Masalah &amp; Progres');

        $this->actingAs($akademikUser)->post(route('tickets.action', $ticket), [
            'action_type' => 'approve_step',
        ])->assertRedirect()->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame($yayasan->id, $ticket->current_role_id);
        $this->assertSame('waiting_next_approval', $ticket->status);
    }

    public function test_head_defines_ticket_problems_and_monitors_staff_progress_percent(): void
    {
        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $mahasiswa = User::where('email', 'mahasiswa@kampus.ac.id')->firstOrFail();
        $category = TicketCategory::firstOrFail();

        $this->actingAs($superuser)->post(route('admin.hierarchy.store'), [
            'name' => 'Admin IT Progres',
            'badge_color' => 'indigo',
            'description' => 'Memantau progres staff.',
            'can_create_ticket' => 1,
            'can_receive_ticket' => 1,
            'head_index' => 0,
            'staff' => [
                ['name' => 'Kepala Progres', 'email' => 'kepala.progres@kampus.ac.id', 'keahlian' => 'Koordinasi'],
                ['name' => 'Staff Progres', 'email' => 'staff.progres@kampus.ac.id', 'keahlian' => 'Aplikasi'],
            ],
        ])->assertRedirect();

        $role = Role::where('name', 'Admin IT Progres')->firstOrFail();
        $head = User::where('email', 'kepala.progres@kampus.ac.id')->firstOrFail();
        $staff = User::where('email', 'staff.progres@kampus.ac.id')->firstOrFail();

        $ticket = Ticket::create([
            'ticket_number' => 'TKT-TEST-9100',
            'title' => 'Perbaikan modul nilai',
            'description' => 'Beberapa bagian modul nilai tidak berjalan.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $mahasiswa->id,
            'creator_role_id' => $mahasiswa->role_id,
            'current_role_id' => $role->id,
            'assigned_user_id' => $staff->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($staff)->post(route('tickets.problems.store', $ticket), [
            'title' => "Login gagal\nNilai hilang",
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($head)->post(route('tickets.problems.store', $ticket), [
            'title' => "Login gagal\nNilai hilang",
        ])->assertRedirect()->assertSessionHas('success');

        $ticket->refresh()->load('problems');
        $this->assertNull($ticket->progressPercent());
        $this->assertCount(2, $ticket->problems);

        $this->actingAs($head)->post(route('tickets.progress', $ticket), [
            'progress_percent' => 50,
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($staff)->post(route('tickets.progress', $ticket), [
            'progress_percent' => 50,
            'note' => 'Masih ada kendala akses database.',
        ])->assertRedirect()->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame(50, $ticket->progressPercent());

        $this->actingAs($head)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertDontSee('Progres Staff')
            ->assertDontSee('Sesuai & bagikan')
            ->assertSee('Staff Progres')
            ->assertSee('1 tiket sedang dikerjakan')
            ->assertSee('Detail')
            ->assertSee('Perbaikan modul nilai')
            ->assertSee('50%')
            ->assertSee('Masih ada kendala akses database.');

        $ticket->forceFill([
            'priority_rank' => 4,
            'priority' => 'low',
            'created_at' => now()->subMinutes(2),
        ])->save();
        $urgent = Ticket::create([
            'ticket_number' => 'TKT-TEST-9101',
            'title' => 'Gangguan jaringan urgent',
            'description' => 'Jaringan kampus putus.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $mahasiswa->id,
            'creator_role_id' => $mahasiswa->role_id,
            'current_role_id' => $role->id,
            'assigned_user_id' => $staff->id,
            'priority_rank' => 1,
            'priority' => 'urgent',
            'status' => 'in_progress',
        ]);

        $inboxHtml = $this->actingAs($head)->get(route('tickets.inbox'))->assertOk()->getContent();
        $urgentPos = strpos($inboxHtml, 'Gangguan jaringan urgent');
        $laterPos = strpos($inboxHtml, 'Perbaikan modul nilai');
        $this->assertNotFalse($urgentPos);
        $this->assertNotFalse($laterPos);
        $this->assertLessThan($urgentPos, $laterPos);
        $this->assertStringContainsString('belum dinilai', $inboxHtml);
        $this->assertStringContainsString('Usulan pemohon', $inboxHtml);

        $this->actingAs($head)->post(route('tickets.priority', $urgent), [
            'assessed_priority_rank' => 1,
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($head)->post(route('tickets.priority', $ticket), [
            'assessed_priority_rank' => 4,
        ])->assertRedirect()->assertSessionHas('success');

        $urgent->refresh();
        $ticket->refresh();
        $this->assertSame(1, (int) $urgent->priority_rank);
        $this->assertSame(1, (int) $urgent->assessed_priority_rank);
        $this->assertSame(4, (int) $ticket->assessed_priority_rank);
        $this->assertSame($staff->id, (int) $urgent->assigned_user_id);

        $orderedHtml = $this->actingAs($head)->get(route('tickets.inbox'))->assertOk()->getContent();
        $this->assertLessThan(
            strpos($orderedHtml, 'Perbaikan modul nilai'),
            strpos($orderedHtml, 'Gangguan jaringan urgent')
        );

        $this->actingAs($staff)->post(route('tickets.action', $ticket), [
            'action_type' => 'in_progress',
            'progress_percent' => 60,
            'note' => 'baru segini pak',
        ])->assertRedirect()->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame(60, $ticket->progressPercent());

        $this->actingAs($head)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('60%');

        $this->actingAs($staff)->post(route('tickets.progress', $ticket), [
            'progress_percent' => 60,
            'work_status' => 'blocked',
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($staff)->post(route('tickets.progress', $ticket), [
            'progress_percent' => 60,
            'work_status' => 'blocked',
            'note' => 'Akses database ditolak.',
        ])->assertRedirect()->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame('blocked', $ticket->work_status);
        $this->assertSame('in_progress', $ticket->status);

        $this->actingAs($head)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Blocked')
            ->assertSee('Akses database ditolak.');

        $this->actingAs($staff)->post(route('tickets.progress', $ticket), [
            'progress_percent' => 60,
            'work_status' => 'finished',
            'note' => 'Pengerjaan sudah selesai.',
        ])->assertRedirect()->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame('finished', $ticket->work_status);
        $this->assertSame('in_progress', $ticket->status);

        $this->actingAs($staff)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Delivered')
            ->assertSee('In progress')
            ->assertSee('Blocked')
            ->assertSee('Finished')
            ->assertSee('Sudah dikerjakan (%)')
            ->assertDontSee('Tindak Lanjut Role')
            ->assertDontSee('Setujui Final');

        $this->actingAs($head)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Masalah & Progres')
            ->assertSee('Tindak Lanjut Role')
            ->assertSee('Login gagal')
            ->assertSee('50%')
            ->assertDontSee('Sudah dikerjakan (%)');

        $this->actingAs($head)->post(route('tickets.action', $ticket), [
            'action_type' => 'resolve',
            'note' => 'Pekerjaan modul nilai sudah selesai.',
        ])->assertRedirect();

        $this->actingAs($head)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertDontSee('Perbaikan modul nilai');

        $this->actingAs($head)->get(route('tickets.history'))
            ->assertOk()
            ->assertSee('Perbaikan modul nilai')
            ->assertSee('Edit');

        $this->actingAs($staff)->post(route('tickets.reopen', $ticket), [
            'note' => 'Saya mau ubah sendiri.',
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($head)->post(route('tickets.reopen', $ticket), [
            'note' => 'Nilai transkrip masih kosong.',
        ])->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame('in_progress', $ticket->status);
        $this->assertNull($ticket->resolved_at);

        $this->actingAs($head)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Perbaikan modul nilai');

        $this->actingAs($mahasiswa)->get(route('tickets.history'))
            ->assertOk()
            ->assertDontSee('Perbaikan modul nilai');
    }

    public function test_employees_sub_sidebar_and_it_dummy_workflow_work_end_to_end(): void
    {
        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $supervisorIt = User::where('email', 'supervisor.it@kampus.ac.id')->firstOrFail();
        $adminItLead = User::where('email', 'admin.it@kampus.ac.id')->firstOrFail();
        $staffJaringan = User::where('email', 'fajar.jaringan@kampus.ac.id')->firstOrFail();
        $staffAplikasi = User::where('email', 'nadia.aplikasi@kampus.ac.id')->firstOrFail();
        $staffLab = User::where('email', 'staff.it@kampus.ac.id')->firstOrFail();

        // 1. Sub-Sidebar Karyawan tampil dan memuat daftar karyawan IT (Supervisor IT, Admin IT, Staff)
        $this->actingAs($superuser)->get(route('admin.users.employees'))
            ->assertOk()
            ->assertSee('Management Karyawan', false)
            ->assertSee($supervisorIt->name, false)
            ->assertSee($adminItLead->name, false)
            ->assertSee($staffJaringan->name, false)
            ->assertSee('Jaringan & Server', false)
            ->assertSee($staffLab->name, false);

        // 2. Request IT baru (TKT-202610-0005) masuk ke Inbox Supervisor IT lengkap dengan gambar bukti
        $incomingItTicket = Ticket::where('ticket_number', 'TKT-202610-0005')->firstOrFail();
        $this->actingAs($supervisorIt)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Error 502 Bad Gateway Saat Sinkronisasi KRS di Portal SIAKAD')
            ->assertSee('Fajar Nugroho, S.T.')
            ->assertSee('70%');

        // 3. Supervisor IT menetapkan priority, menginput poin pekerjaan beserta bobot % (30%, 50%, 20%), dan men-deliver ke Staff Aplikasi (Nadia)
        $this->actingAs($supervisorIt)->post(route('tickets.action', $incomingItTicket), [
            'action_type' => 'in_progress',
            'priority_rank' => 1,
            'assigned_user_id' => $staffAplikasi->id,
            'problem_items' => [
                ['title' => 'Cek log error PHP-FPM & Nginx server SIAKAD', 'weight_percent' => 30],
                ['title' => 'Optimasi query sinkronisasi KRS & restart worker', 'weight_percent' => 50],
                ['title' => 'Uji akses halaman KRS bersama bagian Akademik', 'weight_percent' => 20],
            ],
            'note' => 'Tolong segera dikerjakan sesuai urutan poin prioritas.',
        ])->assertRedirect()->assertSessionHas('success');

        $incomingItTicket->refresh()->load('problems');
        $this->assertSame($staffAplikasi->id, (int) $incomingItTicket->assigned_user_id);
        $this->assertCount(3, $incomingItTicket->problems);
        $this->assertSame(0, $incomingItTicket->progressPercent());

        // 4. Staff (Nadia) membuka tiket, mencentang poin pertama (30%) -> progres otomatis menjadi 30%, lalu mencentang poin kedua (50%) -> progres otomatis menjadi 80%
        $firstPoint = $incomingItTicket->problems[0];
        $secondPoint = $incomingItTicket->problems[1];

        $this->actingAs($staffAplikasi)->post(route('tickets.problems.toggle', [$incomingItTicket, $firstPoint]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $incomingItTicket->refresh();
        $this->assertSame(30, $incomingItTicket->progressPercent());

        $this->actingAs($staffAplikasi)->post(route('tickets.problems.toggle', [$incomingItTicket, $secondPoint]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $incomingItTicket->refresh();
        $this->assertSame(80, $incomingItTicket->progressPercent());

        // 5. Supervisor IT melihat progres pengerjaan Nadia (80%) secara real-time di Inbox Supervisor IT
        $this->actingAs($supervisorIt)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Nadia Kusuma, S.Kom.')
            ->assertSee('80%');
    }

    public function test_head_sees_monthly_work_report_and_requester_sees_division_workload(): void
    {
        $head = User::where('email', 'admin.it@kampus.ac.id')->firstOrFail();
        $staff = User::where('email', 'fajar.jaringan@kampus.ac.id')->firstOrFail();
        $pemohon = User::where('email', 'kaprodi@kampus.ac.id')->firstOrFail();
        $role = Role::where('name', 'Admin IT')->firstOrFail();
        $category = TicketCategory::firstOrFail();

        Ticket::create([
            'ticket_number' => 'TKT-TEST-9200',
            'title' => 'Perbaikan jaringan lab',
            'description' => 'Kabel lab putus.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $pemohon->id,
            'creator_role_id' => $pemohon->role_id,
            'current_role_id' => $role->id,
            'assigned_user_id' => $staff->id,
            'status' => 'in_progress',
            'work_status' => 'blocked',
            'progress_percent' => 40,
            'progress_note' => 'Catatan internal jangan tampil',
        ]);

        $this->actingAs($head)->get(route('tickets.report', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Laporan Bulanan')
            ->assertSee('Fajar Nugroho, S.T.')
            ->assertSee('Blocked')
            ->assertSee('Perbaikan jaringan lab')
            ->assertSee('40%')
            ->assertSee('Catatan internal jangan tampil');

        $this->actingAs($staff)->get(route('tickets.report'))
            ->assertOk()
            ->assertSee('hanya untuk kepala role')
            ->assertDontSee('Perbaikan jaringan lab');

        Ticket::create([
            'ticket_number' => 'TKT-TEST-9201',
            'title' => 'Tiket yang sudah selesai',
            'description' => 'Pekerjaan selesai.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $pemohon->id,
            'creator_role_id' => $pemohon->role_id,
            'current_role_id' => $role->id,
            'assigned_user_id' => $staff->id,
            'status' => 'in_progress',
            'work_status' => 'finished',
            'progress_percent' => 100,
        ]);

        $this->actingAs($pemohon)->get(route('tickets.workload'))
            ->assertOk()
            ->assertSee('Pekerjaan Divisi')
            ->assertSee('Admin IT')
            ->assertSee('Perbaikan jaringan lab')
            ->assertSee('Blocked')
            ->assertSee('Fajar Nugroho, S.T.')
            ->assertSee('Tiket yang sudah selesai')
            ->assertSee('Selesai')
            ->assertDontSee('Catatan internal jangan tampil');
    }

    public function test_finished_work_is_counted_in_the_month_it_was_finished(): void
    {
        $head = User::where('email', 'admin.it@kampus.ac.id')->firstOrFail();
        $staff = User::where('email', 'fajar.jaringan@kampus.ac.id')->firstOrFail();
        $pemohon = User::where('email', 'kaprodi@kampus.ac.id')->firstOrFail();
        $role = Role::where('name', 'Admin IT')->firstOrFail();
        $category = TicketCategory::firstOrFail();

        $ticket = Ticket::create([
            'ticket_number' => 'TKT-TEST-9300',
            'title' => 'Tiket lintas bulan',
            'description' => 'Delivered Oktober, selesai Desember.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $pemohon->id,
            'creator_role_id' => $pemohon->role_id,
            'current_role_id' => $role->id,
            'assigned_user_id' => $staff->id,
            'status' => 'in_progress',
            'work_status' => 'finished',
            'progress_percent' => 100,
        ]);
        $ticket->forceFill([
            'delivered_at' => '2026-10-15 09:00:00',
            'finished_at' => '2026-12-02 09:00:00',
            'created_at' => '2026-10-15 09:00:00',
            'updated_at' => '2026-12-02 09:00:00',
        ])->save();

        $this->actingAs($head)->get(route('tickets.report', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Tiket lintas bulan')
            ->assertSee('Status bulan ini: Delivered')
            ->assertDontSee('Status bulan ini: Finished');

        $this->actingAs($head)->get(route('tickets.report', ['month' => '2026-12']))
            ->assertOk()
            ->assertSee('Tiket lintas bulan')
            ->assertSee('Status bulan ini: Finished')
            ->assertDontSee('Status bulan ini: Delivered');
    }

    public function test_clean_table_ui_supports_inline_priority_change_and_admin_it_triage(): void
    {
        $adminIt = User::where('email', 'admin.it@kampus.ac.id')->firstOrFail();
        $supervisorIt = User::where('email', 'supervisor.it@kampus.ac.id')->firstOrFail();
        $mahasiswa = User::where('email', 'mahasiswa@kampus.ac.id')->firstOrFail();
        $category = TicketCategory::firstOrFail();

        $ticket = Ticket::create([
            'ticket_number' => 'TKT-TEST-ADMIN-01',
            'title' => 'Kendala printer dan scanner lab',
            'description' => 'Printer tidak merespon di lab komputasi.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $mahasiswa->id,
            'creator_role_id' => $mahasiswa->role_id,
            'current_role_id' => $adminIt->role_id,
            'status' => 'open',
            'priority' => 'low',
        ]);

        // 1. Inbox memuat UI clean dengan stat cards (Open Tickets, Pending Response) & table
        $this->actingAs($adminIt)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Open Tickets')
            ->assertSee('Pending Response')
            ->assertSee('Set Prioritas')
            ->assertSee($ticket->ticket_number);

        // 2. Admin IT dapat mengubah priority langsung via AJAX JSON
        $response = $this->actingAs($adminIt)->postJson(route('tickets.priority', $ticket), [
            'priority' => 'urgent',
        ]);
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'priority' => 'urgent',
            ]);

        $ticket->refresh();
        $this->assertSame('urgent', $ticket->priority);
        $this->assertSame(1, (int) $ticket->priority_rank);
        $this->assertSame(1, (int) $ticket->assessed_priority_rank);

        // 3. Filter priority di Inbox bekerja
        $this->actingAs($adminIt)->get(route('tickets.inbox', ['priority' => 'urgent']))
            ->assertOk()
            ->assertSee($ticket->ticket_number);
    }

    public function test_role_based_mvc_dashboards_render_correctly_for_staff_admin_and_head(): void
    {
        $adminIt = User::where('email', 'admin.it@kampus.ac.id')->firstOrFail();
        $supervisorIt = User::where('email', 'supervisor.it@kampus.ac.id')->firstOrFail();
        $staff = User::where('email', 'fajar.jaringan@kampus.ac.id')->firstOrFail();

        // 1. Staff Pelaksana (Fajar) melihat dashboard personal tiketnya, bukan widget kepala yang kosong
        $this->actingAs($staff)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Dashboard Staff IT — Antrean Pekerjaan Saya')
            ->assertSee('Tiket Aktif Saya')
            ->assertSee('Profil & Beban Kerja')
            ->assertSee('Detail')
            ->assertDontSee('Belum ada staff yang mengerjakan tiket');

        // 2. Admin IT melihat dashboard Helpdesk dengan kapasitas staf IT dan tombol Detail
        $this->actingAs($adminIt)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Dashboard Admin IT — Manajemen & Triase Seluruh Tiket')
            ->assertSee('Kapasitas & Beban Kerja Tim Staff')
            ->assertSee('Detail');

        // 3. Kepala Staff / Supervisor IT melihat dashboard pengawasan dengan Staff Progres dan Detail
        $this->actingAs($supervisorIt)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee('Dashboard Kepala Staff — Antrean & Progres Tim')
            ->assertSee('Staff Progres')
            ->assertSee('Detail');
    }

    public function test_dedicated_staff_progress_page_and_sidebar_menu(): void
    {
        $supervisorIt = User::where('email', 'supervisor.it@kampus.ac.id')->firstOrFail();
        $adminIt = User::where('email', 'admin.it@kampus.ac.id')->firstOrFail();

        // 1. Menu 'Staff Progres' tampil di sidebar untuk supervisor dan admin IT
        $this->actingAs($supervisorIt)->get(route('tickets.inbox'))
            ->assertOk()
            ->assertSee(route('tickets.staff-progress'));

        // 2. Akses halaman khusus Staff Progres
        $response = $this->actingAs($supervisorIt)->get(route('tickets.staff-progress'));
        $response->assertOk()
            ->assertSee('Staff Progres')
            ->assertSee('Total Tim Staf')
            ->assertSee('Daftar Tiket yang Sedang Dikerjakan Seluruh Staf')
            ->assertSee('Detail');

        // 3. Admin IT juga dapat mengakses halaman Staff Progres
        $this->actingAs($adminIt)->get(route('tickets.staff-progress'))
            ->assertOk()
            ->assertSee('Staff Progres')
            ->assertSee('Total Tim Staf');
    }

    public function test_clean_login_page_renders_and_supports_authentication(): void
    {
        // 1. Halaman login bersih tanpa hardcoded dummy password dan tanpa ratusan user
        $response = $this->get(route('login'));
        $response->assertOk()
            ->assertSee('SuperTicket')
            ->assertSee('Masuk ke Akun')
            ->assertSee('Alamat Email')
            ->assertSee('Kata Sandi')
            ->assertDontSee('Password default akun demo:');

        // 2. Login dengan kredensial yang valid
        $loginResponse = $this->post(route('login.submit'), [
            'email' => 'superuser@kampus.ac.id',
            'password' => 'password',
        ]);
        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // 3. Akses kembali ke /login saat sudah authenticated akan di-redirect ke dashboard
        $this->get(route('login'))->assertRedirect(route('dashboard'));
    }

    public function test_superadmin_dashboard_refactor_clean_ui_and_removes_role_hierarchy_tree(): void
    {
        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $mahasiswa = User::where('email', 'mahasiswa@kampus.ac.id')->firstOrFail();

        // 1. Dashboard Superadmin tampil dengan UI modern & KPI eksekutif (tanpa hirarki role & tanpa pintasan sistem)
        $response = $this->actingAs($superuser)->get(route('dashboard'));
        $response->assertOk()
            ->assertSee('Dashboard Super Admin')
            ->assertSee('Total Tiket')
            ->assertSee('Sedang Diproses')
            ->assertSee('Tiket Selesai')
            ->assertSee('Pengguna Civitas')
            ->assertSee('Tiket Terbaru Seluruh Kampus')
            ->assertSee('Distribusi Status Tiket')
            ->assertSee('Aktivitas Terkini Sistem')
            ->assertDontSee('Struktur Hirarki Role')
            ->assertDontSee('Pintasan Sistem')
            ->assertDontSee(route('admin.users.employees'));

        // 2. Dashboard Mahasiswa / civitas umum juga bersih tanpa Struktur Hirarki Role
        $mhsResponse = $this->actingAs($mahasiswa)->get(route('dashboard'));
        $mhsResponse->assertOk()
            ->assertSee('Tiket Terbaru Saya')
            ->assertDontSee('Struktur Hirarki Role');
    }

    public function test_header_notification_dropdown_alerts_incoming_and_completed_tickets(): void
    {
        $head = User::where('email', 'admin.it@kampus.ac.id')->firstOrFail();
        $staff = User::where('email', 'fajar.jaringan@kampus.ac.id')->firstOrFail();
        $mhs = User::where('email', 'mahasiswa@kampus.ac.id')->firstOrFail();
        $category = TicketCategory::firstOrFail();

        // 1. Buat tiket baru yang ditujukan ke role IT (Tiket Masuk)
        $incomingTicket = Ticket::create([
            'ticket_number' => 'TKT-NOTIF-001',
            'title' => 'Notif Tiket Masuk Baru',
            'description' => 'Kendala pada printer prodi.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $mhs->id,
            'creator_role_id' => $mhs->role_id,
            'current_role_id' => $head->role_id,
            'status' => 'open',
            'work_status' => 'not_started',
            'priority' => 'high',
        ]);

        // 2. Buat tiket yang sudah selesai dikerjakan (Tiket Selesai)
        $completedTicket = Ticket::create([
            'ticket_number' => 'TKT-NOTIF-002',
            'title' => 'Notif Tiket Pengerjaan Tuntas',
            'description' => 'Aplikasi sudah di-update.',
            'category_id' => $category->id,
            'workflow_type' => 'escalation',
            'creator_id' => $mhs->id,
            'creator_role_id' => $mhs->role_id,
            'current_role_id' => $head->role_id,
            'assigned_user_id' => $staff->id,
            'status' => 'resolved',
            'work_status' => 'finished',
            'progress_percent' => 100,
            'resolved_at' => now(),
        ]);

        // 3. Kepala IT mengakses halaman dashboard
        $response = $this->actingAs($head)->get(route('dashboard'));
        $response->assertOk()
            ->assertSee('Notifikasi')
            ->assertSee('Semua')
            ->assertSee('Masuk')
            ->assertSee('Selesai');

        // Verifikasi payload hex notifikasi memuat tiket masuk dan tiket selesai
        $html = $response->getContent();
        preg_match("/const hex = '([a-f0-9]+)';/", $html, $matches);
        $this->assertNotEmpty($matches, 'Payload hex notifikasi harus ditemukan di markup header.');

        $decodedJson = hex2bin($matches[1]);
        $this->assertStringContainsString('TKT-NOTIF-001', $decodedJson);
        $this->assertStringContainsString('TKT-NOTIF-002', $decodedJson);
        $this->assertStringContainsString('Tiket Masuk', $decodedJson);
        $this->assertStringContainsString('Tiket Selesai', $decodedJson);
    }
}




