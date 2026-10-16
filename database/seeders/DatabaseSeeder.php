<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Role & Struktur Hirarki (Self-referencing parent_id)
        $superuserRole = Role::create([
            'name' => 'Superuser',
            'slug' => 'superuser',
            'parent_id' => null,
            'level' => 0,
            'badge_color' => 'rose',
            'icon' => 'shield-check',
            'description' => 'Administrator tertinggi yang mengelola seluruh menu dinamis, role, pohon hirarki, dan konfigurasi sistem.',
            'is_superuser' => true,
            'can_create_ticket' => true,
            'can_receive_ticket' => true,
        ]);

        $dekanRole = Role::create([
            'name' => 'Dekan',
            'slug' => 'dekan',
            'parent_id' => null,
            'level' => 0,
            'badge_color' => 'violet',
            'icon' => 'building-library',
            'description' => 'Pimpinan tertinggi fakultas. Menerima eskalasi strategis atau approval akhir dari Kaprodi.',
            'is_superuser' => false,
            'can_create_ticket' => true,
            'can_receive_ticket' => true,
        ]);

        $kaprodiRole = Role::create([
            'name' => 'Kaprodi',
            'slug' => 'kaprodi',
            'parent_id' => $dekanRole->id,
            'level' => 1,
            'badge_color' => 'amber',
            'icon' => 'academic-cap',
            'description' => 'Ketua Program Studi. Hirarki di atas Dosen dan berada di bawah Dekan.',
            'is_superuser' => false,
            'can_create_ticket' => true,
            'can_receive_ticket' => true,
        ]);

        $dosenRole = Role::create([
            'name' => 'Dosen',
            'slug' => 'dosen',
            'parent_id' => $kaprodiRole->id,
            'level' => 2,
            'badge_color' => 'emerald',
            'icon' => 'user-group',
            'description' => 'Dosen Wali & Pembimbing Akademik. Menerima tiket langsung dari Mahasiswa dan memiliki hirarki ke Kaprodi.',
            'is_superuser' => false,
            'can_create_ticket' => true,
            'can_receive_ticket' => true,
        ]);

        $mahasiswaRole = Role::create([
            'name' => 'Mahasiswa',
            'slug' => 'mahasiswa',
            'parent_id' => $dosenRole->id,
            'level' => 3,
            'badge_color' => 'indigo',
            'icon' => 'user',
            'description' => 'Mahasiswa aktif yang mengajukan tiket akademik, bimbingan, surat, atau keluhan ke Dosen.',
            'is_superuser' => false,
            'can_create_ticket' => true,
            'can_receive_ticket' => false,
        ]);

        Role::recalculateLevels();

        // 2. Buat Users untuk masing-masing Role
        $superuser = User::create([
            'name' => 'Super Admin Kampus',
            'email' => 'superuser@kampus.ac.id',
            'password' => Hash::make('password'),
            'role_id' => $superuserRole->id,
            'user_type' => 'admin',
            'identity_number' => 'SUP-001',
            'department' => 'Direktorat Sistem Informasi',
            'jabatan' => 'Superuser Sistem',
            'avatar_color' => 'rose',
        ]);

        $dekan = User::create([
            'name' => 'Prof. Dr. Hendra Wijaya, M.Kom.',
            'email' => 'dekan@kampus.ac.id',
            'password' => Hash::make('password'),
            'role_id' => $dekanRole->id,
            'user_type' => 'pengajar',
            'identity_number' => 'NIP-19720411',
            'department' => 'Fakultas Teknologi, Hukum dan Bisnis',
            'fakultas' => 'FAKULTAS TEKNOLOGI, HUKUM DAN BISNIS',
            'jabatan' => 'Dekan Fakultas',
            'avatar_color' => 'violet',
        ]);

        $kaprodi = User::create([
            'name' => 'Dr. Rina Kartika, S.T., M.T.',
            'email' => 'kaprodi@kampus.ac.id',
            'password' => Hash::make('password'),
            'role_id' => $kaprodiRole->id,
            'supervisor_id' => $dekan->id,
            'user_type' => 'pengajar',
            'identity_number' => 'NIDN-0412088501',
            'department' => 'Informatika',
            'fakultas' => 'FAKULTAS TEKNOLOGI, HUKUM DAN BISNIS',
            'jabatan' => 'Kaprodi Informatika',
            'avatar_color' => 'amber',
        ]);

        $dosen = User::create([
            'name' => 'Budi Santoso, S.Kom., M.Cs.',
            'email' => 'dosen@kampus.ac.id',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'supervisor_id' => $kaprodi->id,
            'user_type' => 'pengajar',
            'identity_number' => 'NIDN-0425099002',
            'department' => 'Informatika',
            'fakultas' => 'FAKULTAS TEKNOLOGI, HUKUM DAN BISNIS',
            'jabatan' => 'Dosen Tetap',
            'avatar_color' => 'emerald',
        ]);

        $mahasiswa1 = User::create([
            'name' => 'Andi Pratama',
            'email' => 'mahasiswa@kampus.ac.id',
            'password' => Hash::make('password'),
            'role_id' => $mahasiswaRole->id,
            'supervisor_id' => $dosen->id,
            'user_type' => 'mahasiswa',
            'identity_number' => '2310511042',
            'department' => 'Informatika',
            'fakultas' => 'FAKULTAS TEKNOLOGI, HUKUM DAN BISNIS',
            'angkatan' => 2023,
            'semester' => 5,
            'status_akademik' => 'Aktif',
            'avatar_color' => 'indigo',
        ]);

        $mahasiswa2 = User::create([
            'name' => 'Siti Nabila Putri',
            'email' => 'siti@kampus.ac.id',
            'password' => Hash::make('password'),
            'role_id' => $mahasiswaRole->id,
            'supervisor_id' => $dosen->id,
            'user_type' => 'mahasiswa',
            'identity_number' => '2410511018',
            'department' => 'Informatika',
            'fakultas' => 'FAKULTAS TEKNOLOGI, HUKUM DAN BISNIS',
            'angkatan' => 2024,
            'semester' => 3,
            'status_akademik' => 'Aktif',
            'avatar_color' => 'sky',
        ]);

        // 3. Buat Menu Dinamis (SuperApp Launcher & Sidebar beserta Sub-Sidebar Manajemen Pengguna)
        $menusData = [
            [
                'name' => 'Beranda SuperApp',
                'slug' => 'dashboard',
                'route_name' => 'dashboard',
                'icon' => 'home',
                'color' => 'indigo',
                'group' => 'main',
                'badge_text' => 'Utama',
                'description' => 'Pusat kendali utama, statistik layanan, dan peluncur cepat SuperApp.',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'sort_order' => 1,
                'roles' => [$superuserRole->id, $dekanRole->id, $kaprodiRole->id, $dosenRole->id, $mahasiswaRole->id],
            ],
            [
                'name' => 'Tiket Saya',
                'slug' => 'tickets-my',
                'route_name' => 'tickets.my',
                'icon' => 'ticket',
                'color' => 'indigo',
                'group' => 'ticketing',
                'badge_text' => 'Aktif & Buat Baru',
                'description' => 'Daftar tiket yang sedang diproses beserta tombol Buat Tiket Baru.',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'sort_order' => 2,
                'roles' => [$superuserRole->id, $dekanRole->id, $kaprodiRole->id, $dosenRole->id, $mahasiswaRole->id],
            ],
            [
                'name' => 'Riwayat Tiket',
                'slug' => 'tickets-history',
                'route_name' => 'tickets.history',
                'icon' => 'document-check',
                'color' => 'emerald',
                'group' => 'ticketing',
                'badge_text' => 'Sudah Approve',
                'description' => 'Riwayat tiket yang sudah berstatus Approve / Selesai beserta preview dokumen.',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'sort_order' => 3,
                'roles' => [$superuserRole->id, $dekanRole->id, $kaprodiRole->id, $dosenRole->id, $mahasiswaRole->id],
            ],
            [
                'name' => 'Tiket Masuk Role',
                'slug' => 'tickets-inbox',
                'route_name' => 'tickets.inbox',
                'icon' => 'inbox-arrow-down',
                'color' => 'amber',
                'group' => 'ticketing',
                'badge_text' => 'Inbox Hirarki',
                'description' => 'Daftar tiket masuk dari role di bawah Anda untuk diatur prioritasnya, disetujui, atau dieskalasi.',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'sort_order' => 4,
                'roles' => [$superuserRole->id, $dekanRole->id, $kaprodiRole->id, $dosenRole->id],
            ],
            [
                'name' => 'Manajemen Hirarki & Role',
                'slug' => 'admin-hierarchy',
                'route_name' => 'admin.hierarchy',
                'icon' => 'share',
                'color' => 'violet',
                'group' => 'management',
                'badge_text' => 'Superuser',
                'description' => 'Buat role baru dan atur struktur pohon hirarki (misal Mahasiswa → Dosen → Kaprodi).',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'sort_order' => 5,
                'roles' => [$superuserRole->id],
            ],
            [
                'name' => 'Manajemen Menu Dinamis',
                'slug' => 'admin-menus',
                'route_name' => 'admin.menus',
                'icon' => 'squares-plus',
                'color' => 'rose',
                'group' => 'management',
                'badge_text' => 'Superuser',
                'description' => 'Atur menu SuperApp, ikon, urutan tampil, dan hak akses menu untuk setiap role.',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'sort_order' => 6,
                'roles' => [$superuserRole->id],
            ],
            [
                'name' => 'Kategori & Alur Tiket',
                'slug' => 'admin-categories',
                'route_name' => 'admin.categories',
                'icon' => 'adjustments-horizontal',
                'color' => 'teal',
                'group' => 'management',
                'badge_text' => 'Workflow',
                'description' => 'Konfigurasi kategori layanan, role tujuan awal, dan mode Eskalasi vs Approval Berjenjang.',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'sort_order' => 7,
                'roles' => [$superuserRole->id],
            ],
            [
                'name' => 'Manajemen Pengguna',
                'slug' => 'admin-users',
                'route_name' => 'admin.users',
                'icon' => 'users',
                'color' => 'fuchsia',
                'group' => 'management',
                'badge_text' => 'SIAKAD API',
                'description' => 'Kelola data Mahasiswa dan Pengajar (Dosen, Kaprodi, Dekan) yang disinkronkan dari REST API SIAKAD.',
                'show_in_sidebar' => true,
                'show_in_launcher' => true,
                'sort_order' => 8,
                'roles' => [$superuserRole->id],
            ],
        ];

        $createdMenus = [];
        foreach ($menusData as $item) {
            $roleIds = $item['roles'];
            unset($item['roles']);
            $menu = Menu::create($item);
            $menu->roles()->sync($roleIds);
            $createdMenus[$menu->slug] = $menu;
        }

        // Sub-Sidebar di bawah Manajemen Pengguna: 1. Mahasiswa, 2. Pengajar
        if (isset($createdMenus['admin-users'])) {
            $parentMenu = $createdMenus['admin-users'];

            $subStudents = Menu::create([
                'parent_id' => $parentMenu->id,
                'name' => 'Mahasiswa',
                'slug' => 'admin-users-students',
                'route_name' => 'admin.users.students',
                'icon' => 'academic-cap',
                'color' => 'indigo',
                'group' => 'management',
                'badge_text' => 'SIAKAD',
                'description' => 'Direktori & manajemen Mahasiswa dari REST API SIAKAD.',
                'show_in_sidebar' => true,
                'show_in_launcher' => false,
                'sort_order' => 1,
            ]);
            $subStudents->roles()->sync([$superuserRole->id]);

            $subLecturers = Menu::create([
                'parent_id' => $parentMenu->id,
                'name' => 'Pengajar',
                'slug' => 'admin-users-lecturers',
                'route_name' => 'admin.users.lecturers',
                'icon' => 'user-group',
                'color' => 'amber',
                'group' => 'management',
                'badge_text' => 'Dosen/Kaprodi',
                'description' => 'Direktori Pengajar (Dosen, Kaprodi, Dekan) dari REST API SIAKAD.',
                'show_in_sidebar' => true,
                'show_in_launcher' => false,
                'sort_order' => 2,
            ]);
            $subLecturers->roles()->sync([$superuserRole->id]);
        }

        // 4. Buat Kategori Tiket dengan Konfigurasi Hirarki (Eskalasi & Approval Berjenjang)
        $catKrs = TicketCategory::create([
            'name' => 'Konsultasi KRS & Perwalian Akademik',
            'slug' => 'konsultasi-krs-perwalian',
            'icon' => 'academic-cap',
            'color' => 'indigo',
            'description' => 'Pengajuan perubahan KRS, tambah/batal mata kuliah, atau konsultasi akademik ke Dosen Wali (bisa dieskalasi ke Kaprodi bila kuota kelas penuh).',
            'initial_target_role_id' => $dosenRole->id,
            'workflow_type' => 'escalation',
            'max_approval_role_id' => $kaprodiRole->id,
            'is_active' => true,
        ]);

        $catSkripsi = TicketCategory::create([
            'name' => 'Pengajuan Judul Skripsi / Tugas Akhir',
            'slug' => 'pengajuan-judul-skripsi',
            'icon' => 'document-check',
            'color' => 'emerald',
            'description' => 'Wajib melalui Approval Berjenjang: disetujui oleh Dosen Pembimbing terlebih dahulu, lalu otomatis naik ke Kaprodi untuk pengesahan.',
            'initial_target_role_id' => $dosenRole->id,
            'workflow_type' => 'tiered_approval',
            'max_approval_role_id' => $kaprodiRole->id,
            'is_active' => true,
        ]);

        $catDispensasi = TicketCategory::create([
            'name' => 'Dispensasi & Pendanaan Lomba Nasional',
            'slug' => 'dispensasi-lomba-nasional',
            'icon' => 'trophy',
            'color' => 'amber',
            'description' => 'Approval Berjenjang 3 Tingkat mulai dari Dosen → Kaprodi → hingga pengesahan akhir oleh Dekan.',
            'initial_target_role_id' => $dosenRole->id,
            'workflow_type' => 'tiered_approval',
            'max_approval_role_id' => $dekanRole->id,
            'is_active' => true,
        ]);

        $catNilai = TicketCategory::create([
            'name' => 'Klarifikasi Nilai & Administrasi Perkuliahan',
            'slug' => 'klarifikasi-nilai-perkuliahan',
            'icon' => 'clipboard-document-list',
            'color' => 'rose',
            'description' => 'Tiket masuk ke Dosen pengampu/wali, dapat diselesaikan langsung atau dieskalasi ke Kaprodi.',
            'initial_target_role_id' => $dosenRole->id,
            'workflow_type' => 'escalation',
            'max_approval_role_id' => $kaprodiRole->id,
            'is_active' => true,
        ]);

        // 5. Buat Sample Dokumen PDF untuk Preview & Contoh Tiket Lintas Hirarki
        $samplePdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]/Parent 2 0 R/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>endobj\n4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n5 0 obj<</Length 128>>stream\nBT /F1 16 Tf 50 760 Td (DOKUMEN LAMPIRAN TIKET AKADEMIK) Tj /F1 12 Tf 0 -30 Td (Status: Dokumen Pendukung Pengajuan Tiket Mahasiswa) Tj ET\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \n0000000207 00000 n \n0000000269 00000 n \ntrailer<</Size 6/Root 1 0 R>>\nstartxref\n446\n%%EOF";
        \Illuminate\Support\Facades\Storage::disk('public')->put('attachments/proposal_skripsi_siti.pdf', $samplePdfContent);
        \Illuminate\Support\Facades\Storage::disk('public')->put('attachments/surat_dispensasi_gemastik.pdf', $samplePdfContent);

        // Tiket 1: Baru dibuat oleh Mahasiswa Andi -> sedang di Inbox Dosen (Prioritas belum diset)
        $t1 = Ticket::create([
            'ticket_number' => 'TKT-202610-0001',
            'title' => 'Permohonan Buka Kelas Tambahan Mata Kuliah Cloud Computing',
            'description' => 'Selamat pagi Pak Budi, saya Andi Pratama (NIM 2310511042). Saat pengisian KRS, kuota kelas Cloud Computing sudah penuh padahal mata kuliah ini prasyarat semester depan. Mohon arahan dan bantuannya Pak.',
            'category_id' => $catKrs->id,
            'priority' => null,
            'workflow_type' => 'escalation',
            'creator_id' => $mahasiswa1->id,
            'creator_role_id' => $mahasiswaRole->id,
            'current_role_id' => $dosenRole->id,
            'assigned_user_id' => $dosen->id,
            'status' => 'open',
            'approval_step' => 1,
        ]);

        TicketLog::create([
            'ticket_id' => $t1->id,
            'user_id' => $mahasiswa1->id,
            'from_role_id' => $mahasiswaRole->id,
            'to_role_id' => $dosenRole->id,
            'action' => 'created',
            'note' => 'Tiket dibuat oleh Mahasiswa dan otomatis diarahkan ke role Dosen sesuai hirarki.',
        ]);

        // Tiket 2: Pengajuan Judul Skripsi oleh Siti -> Sudah di-approve Dosen & Kaprodi (Selesai / Approved) + Ada Dokumen PDF!
        $t2 = Ticket::create([
            'ticket_number' => 'TKT-202610-0002',
            'title' => 'Pengajuan Topik Skripsi: Sistem Deteksi Anomali Jaringan Berbasis Deep Learning',
            'description' => 'Yth. Bapak Dosen Pembimbing dan Ibu Kaprodi, berikut saya ajukan proposal ringkas topik tugas akhir mengenai deteksi anomali trafik jaringan kampus menggunakan arsitektur Transformer.',
            'attachment_path' => 'attachments/proposal_skripsi_siti.pdf',
            'attachment_name' => 'Proposal_Skripsi_Siti_Nabila.pdf',
            'category_id' => $catSkripsi->id,
            'priority' => 'medium',
            'workflow_type' => 'tiered_approval',
            'creator_id' => $mahasiswa2->id,
            'creator_role_id' => $mahasiswaRole->id,
            'current_role_id' => $kaprodiRole->id,
            'assigned_user_id' => $kaprodi->id,
            'status' => 'resolved',
            'approval_step' => 2,
            'resolved_at' => now(),
        ]);

        TicketLog::create([
            'ticket_id' => $t2->id,
            'user_id' => $mahasiswa2->id,
            'from_role_id' => $mahasiswaRole->id,
            'to_role_id' => $dosenRole->id,
            'action' => 'created',
            'note' => 'Tiket pengajuan judul skripsi dibuat oleh Mahasiswa. Lampiran: Proposal_Skripsi_Siti_Nabila.pdf',
        ]);

        TicketLog::create([
            'ticket_id' => $t2->id,
            'user_id' => $dosen->id,
            'from_role_id' => $dosenRole->id,
            'to_role_id' => $kaprodiRole->id,
            'action' => 'approved_step',
            'note' => 'Topik sangat relevan dengan roadmap riset lab. Disetujui di tingkat Dosen dan diteruskan ke Kaprodi.',
        ]);

        TicketLog::create([
            'ticket_id' => $t2->id,
            'user_id' => $kaprodi->id,
            'from_role_id' => $kaprodiRole->id,
            'to_role_id' => $kaprodiRole->id,
            'action' => 'final_approved',
            'note' => 'Judul skripsi disetujui oleh Kaprodi. Silakan lanjutkan penyusunan Bab 1-3.',
        ]);

        // Tiket 3: Tiket Mahasiswa Andi yang juga berada di Inbox Dosen (sedang diproses Dosen)
        $t3 = Ticket::create([
            'ticket_number' => 'TKT-202610-0003',
            'title' => 'Perbaikan Nilai Praktikum Basis Data Lanjut Semester Genap',
            'description' => 'Halo Pak Budi, nilai modul ke-5 praktikum Basis Data saya belum terinput di portal akademik padahal sudah asistensi. Mohon bantuannya untuk sinkronisasi nilai.',
            'category_id' => $catNilai->id,
            'priority' => 'urgent',
            'workflow_type' => 'escalation',
            'creator_id' => $mahasiswa1->id,
            'creator_role_id' => $mahasiswaRole->id,
            'current_role_id' => $dosenRole->id,
            'assigned_user_id' => $dosen->id,
            'status' => 'in_progress',
            'approval_step' => 1,
        ]);

        TicketLog::create([
            'ticket_id' => $t3->id,
            'user_id' => $mahasiswa1->id,
            'from_role_id' => $mahasiswaRole->id,
            'to_role_id' => $dosenRole->id,
            'action' => 'created',
            'note' => 'Tiket dibuat oleh Mahasiswa dan masuk ke dashboard Dosen.',
        ]);

        TicketLog::create([
            'ticket_id' => $t3->id,
            'user_id' => $dosen->id,
            'from_role_id' => $dosenRole->id,
            'to_role_id' => $dosenRole->id,
            'action' => 'in_progress',
            'note' => 'Sedang saya cek rekap nilai dari asisten laboratorium, mohon ditunggu.',
        ]);

        // Tiket 4: Dispensasi Lomba Andi yang sudah di-approve sampai Dekan + Ada Dokumen PDF!
        $t4 = Ticket::create([
            'ticket_number' => 'TKT-202610-0004',
            'title' => 'Permohonan Dispensasi Kuliah & Surat Tugas Finalis Gemastik Nasional',
            'description' => 'Tim kami lolos ke babak final Gemastik selama 4 hari minggu depan. Mohon persetujuan dispensasi kehadiran perkuliahan dan surat tugas resmi fakultas.',
            'attachment_path' => 'attachments/surat_dispensasi_gemastik.pdf',
            'attachment_name' => 'Surat_Undangan_Finalis_Gemastik.pdf',
            'category_id' => $catDispensasi->id,
            'priority' => 'high',
            'workflow_type' => 'tiered_approval',
            'creator_id' => $mahasiswa1->id,
            'creator_role_id' => $mahasiswaRole->id,
            'current_role_id' => $dekanRole->id,
            'assigned_user_id' => $dekan->id,
            'status' => 'resolved',
            'approval_step' => 3,
            'resolved_at' => now(),
        ]);

        TicketLog::create([
            'ticket_id' => $t4->id,
            'user_id' => $mahasiswa1->id,
            'from_role_id' => $mahasiswaRole->id,
            'to_role_id' => $dosenRole->id,
            'action' => 'created',
            'note' => 'Pengajuan dispensasi dibuat oleh Mahasiswa ke Dosen Wali. Lampiran: Surat_Undangan_Finalis_Gemastik.pdf',
        ]);

        TicketLog::create([
            'ticket_id' => $t4->id,
            'user_id' => $dosen->id,
            'from_role_id' => $dosenRole->id,
            'to_role_id' => $kaprodiRole->id,
            'action' => 'approved_step',
            'note' => 'Disetujui Dosen Wali. Diteruskan sesuai hirarki ke Kaprodi.',
        ]);

        TicketLog::create([
            'ticket_id' => $t4->id,
            'user_id' => $kaprodi->id,
            'from_role_id' => $kaprodiRole->id,
            'to_role_id' => $dekanRole->id,
            'action' => 'approved_step',
            'note' => 'Prodi mendukung penuh keikutsertaan tim. Diteruskan ke Dekan untuk surat tugas fakultas.',
        ]);

        TicketLog::create([
            'ticket_id' => $t4->id,
            'user_id' => $dekan->id,
            'from_role_id' => $dekanRole->id,
            'to_role_id' => $dekanRole->id,
            'action' => 'final_approved',
            'note' => 'Disetujui oleh Dekan. Surat tugas dan dispensasi perkuliahan resmi diterbitkan.',
        ]);
    }
}
