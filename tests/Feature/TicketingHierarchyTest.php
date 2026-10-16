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

        // 4. Kaprodi menyelesaikan/menyetujui tiket, lalu Mahasiswa melihat di Riwayat Tiket Saya (Sudah Di-Approve)
        $this->actingAs($kaprodi)->post(route('tickets.action', $ticket), [
            'action_type' => 'resolve',
            'note' => 'Disetujui penambahan kuota.',
        ])->assertRedirect();

        $this->actingAs($mahasiswa)->get(route('tickets.my', ['tab' => 'approved']))
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
            $this->actingAs($user)->get(route('tickets.create'))->assertOk();
        }

        $superuser = User::where('email', 'superuser@kampus.ac.id')->firstOrFail();
        $this->actingAs($superuser)->get(route('admin.hierarchy'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.menus'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.categories'))->assertOk();
        $this->actingAs($superuser)->get(route('admin.users'))->assertOk();
    }
}
