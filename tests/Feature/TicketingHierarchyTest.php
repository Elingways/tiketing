<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // Pastikan Dosen hirarkinya ke Kaprodi
        $this->assertEquals($kaprodiRole->id, $dosenRole->parent_id);

        // Superuser membuat role baru "Asisten Dosen" dan menentukan hirarkinya ke "Dosen"
        $response = $this->actingAs($superuser)->post(route('admin.hierarchy.store'), [
            'name' => 'Asisten Dosen',
            'parent_id' => $dosenRole->id,
            'badge_color' => 'teal',
            'description' => 'Membantu praktikum dan meneruskan tiket ke Dosen.',
            'can_create_ticket' => 1,
            'can_receive_ticket' => 1,
        ]);

        $response->assertRedirect();
        $newRole = Role::where('name', 'Asisten Dosen')->first();
        $this->assertNotNull($newRole);
        $this->assertEquals($dosenRole->id, $newRole->parent_id);

        // Cek rantai hirarki ke atas: Asisten Dosen -> Dosen -> Kaprodi -> Dekan
        $chain = $newRole->getUpwardChainNames();
        $this->assertEquals(['Asisten Dosen', 'Dosen', 'Kaprodi', 'Dekan'], $chain);
    }

    public function test_mahasiswa_creates_ticket_to_dosen_and_dosen_escalates_to_kaprodi(): void
    {
        $mahasiswa = User::where('email', 'mahasiswa@kampus.ac.id')->firstOrFail();
        $dosen = User::where('email', 'dosen@kampus.ac.id')->firstOrFail();
        $kaprodi = User::where('email', 'kaprodi@kampus.ac.id')->firstOrFail();
        $category = TicketCategory::where('workflow_type', 'escalation')->firstOrFail();

        // 1. Mahasiswa membuat tiket baru -> masuk ke Dosen
        $this->actingAs($mahasiswa)->post(route('tickets.store'), [
            'title' => 'Pengajuan Penambahan Kuota KRS',
            'category_id' => $category->id,
            'priority' => 'high',
            'description' => 'Mohon bantuan pembukaan kuota KRS untuk mata kuliah pilihan.',
        ])->assertRedirect();

        $ticket = Ticket::where('title', 'Pengajuan Penambahan Kuota KRS')->firstOrFail();
        $this->assertEquals($dosen->role_id, $ticket->current_role_id);

        // 2. Dosen melihat tiket di dashboard & melakukan eskalasi ke atasan hirarkinya (Kaprodi)
        $this->actingAs($dosen)->post(route('tickets.action', $ticket), [
            'action_type' => 'escalate',
            'note' => 'Mohon kebijakan Ibu Kaprodi untuk penambahan kuota kelas.',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals($kaprodi->role_id, $ticket->current_role_id);
        $this->assertEquals('escalated', $ticket->status);

        // 3. Kaprodi menyelesaikan tiket
        $this->actingAs($kaprodi)->post(route('tickets.action', $ticket), [
            'action_type' => 'resolve',
            'note' => 'Kuota kelas telah ditambah 10 kursi. Silakan isi KRS.',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status);
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
