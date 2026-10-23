# 🧠 Project Memory: USH Ticketing System

> **Tujuan Dokumen:**  
> File ini berfungsi sebagai memori persisten (*long-term memory*) untuk mencatat konteks proyek, arsitektur, keputusan teknis, serta riwayat prompt & instruksi agar asisten AI dan pengembang tidak kehilangan konteks antar sesi percakapan.

---

## 📌 Ringkasan Proyek
- **Nama Aplikasi:** USH Ticketing System (`tikecting`)
- **Framework & Tech Stack:**
  - **Backend:** Laravel (PHP)
  - **Frontend:** Blade Templates, CSS/JS (Vite)
  - **Database:** MySQL / MariaDB
  - **Integrasi Eksternal:** SIAKAD REST API (Sinkronisasi Pengguna: Mahasiswa & Pengajar)
- **Terakhir Diperbarui:** 2026-10-23

---

## 🏛️ Arsitektur & Aturan Logika Bisnis
1. **Sistem Role & Pengguna:**
   - `Admin IT`: Administrator sistem penuh, manajemen menu & akun.
   - `Supervisor IT`: Menerima tiket kategori IT, membagi tiket ke Staff IT dengan bobot kerja (%), memvalidasi progres.
   - `Staff IT`: Mengerjakan tiket yang ditugaskan, progres dihitung otomatis berdasarkan bobot (%) pekerjaan.
   - `Karyawan`: Pengguna internal pemohon tiket / approval hirarki.
   - `Pengajar & Mahasiswa`: Pengguna pemohon tiket layanan akademik/kampus yang disinkronkan via REST API SIAKAD.

2. **Hierarki & Alur Tiket:**
   - Setiap kategori layanan memiliki hierarki approval dinamis (contoh: *Mahasiswa ➔ Akademik ➔ Yayasan*).
   - Tiket kategori IT otomatis di-routing ke **Supervisor IT**.
   - Sistem poin/bobot kerja (%) otomatis menghitung akumulasi kemajuan (progress calculation) tiket staf.
   - Kategori layanan mendukung unggah dan pratinjau (*preview*) dokumen template persyaratan.

3. **Manajemen Tampilan & Navigasi:**
   - Navigasi sidebar dinamis (Manajemen Menu, Kategori Layanan, Manajemen Pengguna dengan sub-sidebar Karyawan, Mahasiswa, Pengajar).

---

## 📋 Aturan Kerja AI (Instruksi Otomatis)
Setiap kali berinteraksi dalam sesi chat:
1. **Wajib Baca:** Periksa `memory.md` di awal percakapan sebelum memberikan rekomendasi atau mengeksekusi fitur baru.
2. **Wajib Update:** Setelah menyelesaikan permintaan/prompt penting:
   - Tambahkan entri baru ke bagian **Riwayat Log Prompt & Solusi**.
   - Perbarui status pada bagian **Daftar Tugas & Status Saat Ini** jika ada status yang berubah.
3. **Integritas Kode:** Pertahankan fungsionalitas yang sudah ada dan jangan menghapus komentar/kode tanpa alasan yang jelas.

---

## 📝 Riwayat Log Prompt & Solusi

### [2026-10-23] Menu Sidebar Khusus "Staff Progres" untuk Pemantauan Kinerja & Beban Kerja Terfokus
- **User Prompt:**
  > "Staff Progres, dibagian staff progres baiknya dibuatkan 1 menu sidebar baru untuk memfokuskan gitu bisa ga ?"
- **Analisis & Keputusan Arsitektur:**
  - Sebelumnya, widget Staff Progres berada di dalam halaman Inbox Tiket (`/tickets/inbox`), yang meskipun informatif, dapat mengaburkan fokus atasan saat ingin memantau murni beban kerja tim teknisi tanpa terdistraksi antrean tiket masuk.
  - Memisahkan pemantauan kinerja staf ke dalam 1 menu sidebar dedicated bernama **"Staff Progres"** (`/tickets/staff-progress`) di bawah grup `ticketing` membuat triase tiket dan pengawasan SDM lebih terfokus dan modular.
- **Tindakan & Solusi:**
  - **Database & Menu:** Menambahkan menu "Staff Progres" (`slug`: `tickets-staff-progress`, `route`: `tickets.staff-progress`, `icon`: `chart-bar`, `group`: `ticketing`, `order`: 3) ke database dan `database/seeders/DatabaseSeeder.php`.
  - **Routing:** Mendaftarkan route `GET /tickets/staff-progress` (`tickets.staff-progress`) di [routes/web.php](file:///Users/mac/USH/tikecting/routes/web.php).
  - **Controller:** Membuat method `staffProgressPage` di [TicketController.php](file:///Users/mac/USH/tikecting/app/Http/Controllers/TicketController.php) yang menghitung metrik agregat tim: Total Staf, Staf Aktif, Tiket Dikerjakan, Rata-rata Progres Tim, Tiket Terkendala (Blocked), serta breakdown daftar tiket per staf.
  - **View:** Membuat halaman mandiri [resources/views/tickets/staff-progress.blade.php](file:///Users/mac/USH/tikecting/resources/views/tickets/staff-progress.blade.php) dengan kartu kapasitas tim, indikator status (Normal / Blocked), preview tiket aktif, checklist pengerjaan, tombol filter/kembali ke inbox, dan modal detail staf interaktif.
  - **Ikon UI:** Menambahkan icon SVG `chart-bar` di [resources/views/partials/icon.blade.php](file:///Users/mac/USH/tikecting/resources/views/partials/icon.blade.php).
  - **Testing:** Menambahkan test `test_dedicated_staff_progress_page_and_sidebar_menu` di [tests/Feature/TicketingHierarchyTest.php](file:///Users/mac/USH/tikecting/tests/Feature/TicketingHierarchyTest.php). Seluruh 17 automated tests (381 assertions) lulus 100%.
- **File Terkait:**
  - `routes/web.php`
  - `app/Http/Controllers/TicketController.php`
  - `resources/views/tickets/staff-progress.blade.php`
  - `resources/views/partials/icon.blade.php`
  - `database/seeders/DatabaseSeeder.php`
  - `tests/Feature/TicketingHierarchyTest.php`
  - `memory.md`
- **Status:** Selesai ✅

---

### [2026-10-23] Arsitektur MVC Dashboard Multi-Role (Admin IT, Kepala Staff, Staff Pelaksana) & Pemulihan Modal Detail Staf
- **User Prompt:**
  > "'/Users/mac/USH/tikecting/image copy 3.png' tadi sebelumnya itu ada detail , sekarang tidak ada , itu digunakan untuk melhiat 1 staff dia memiliki beraapa tiket, kemudian admin juga bisa update priority, menurut saya , kamu harus juga menggunakan struktur mvc, kita fokus ke pembuatan dashboard dari staff admin,kepala staff, biar terstruktur dan tidak bentrok uinya"
  > "oke tetapi kenapa anda ga bikin folder trus didalamnya iisiannya gitu ?"
- **Analisis & Solusi MVC Folder-Based:**
  - **Masalah:** Template tunggal monolitik sebelumnya menyebabkan bentrok UI antar peran; Staff (seperti Syahrul) melihat widget kepala yang kosong ("Belum ada staff yang mengerjakan tiket"), tombol "Detail" staf tersembunyi, dan kolom No. Tiket terpotong karena layout tabel yang kaku.
  - **Model (M):** Menambahkan helper peran di `User.php` (`isHeadOfRole()`, `isAdminIt()`, `isSupervisorIt()`, `isStaff()`) dan penegasan otorisasi Admin IT di `Ticket.php`.
  - **Controller (C):** `TicketController@inbox` mengklasifikasikan `$roleType` secara tegas (`admin`, `head`, `staff`, `general`) serta menghitung metrik & dataset yang sesuai untuk masing-masing peran.
  - **View (V) dengan Struktur Folder Terorganisir:**
    - `resources/views/tickets/inbox.blade.php`: Master shell controller view.
    - `resources/views/tickets/dashboards/admin/index.blade.php`: Dashboard Helpdesk Admin IT (All Tickets, Quick Inline Priority Changer via AJAX, strip kapasitas staf dengan tombol Detail).
    - `resources/views/tickets/dashboards/head/index.blade.php`: Dashboard pengawasan Kepala Staff / Supervisor IT (Staff Progres dengan tombol Detail, antrean delegasi ke staf).
    - `resources/views/tickets/dashboards/staff/index.blade.php`: Dashboard Staff Pelaksana (antrean tiket yang ditugaskan ke dirinya, progress bar checklist %, pelaporan kendala/note kerja, profil beban kerja pribadi).
    - `resources/views/tickets/dashboards/general/index.blade.php`: Dashboard civitas umum.
    - `resources/views/tickets/dashboards/shared/staff-detail-modal.blade.php`: Modal detail staf reusable untuk melihat daftar tiket per staf dan form penilaian prioritas.
  - **Perbaikan Responsivitas Tabel:** Penataan min-width kolom yang proporsional sehingga kolom Ticket ID dan tanggal tidak lagi terpotong pada layar desktop/laptop.
- **File Terkait:**
  - `app/Models/User.php`
  - `app/Models/Ticket.php`
  - `app/Http/Controllers/TicketController.php`
  - `resources/views/tickets/inbox.blade.php`
  - `resources/views/tickets/dashboards/admin/index.blade.php`
  - `resources/views/tickets/dashboards/head/index.blade.php`
  - `resources/views/tickets/dashboards/staff/index.blade.php`
  - `resources/views/tickets/dashboards/general/index.blade.php`
  - `resources/views/tickets/dashboards/shared/staff-detail-modal.blade.php`
  - `tests/Feature/TicketingHierarchyTest.php`
- **Status:** Selesai ✅

---

### [2026-10-23] Redesign UI Tiket Masuk (Helpy Modern SaaS Style) & Penataan Peran Admin IT
- **User Prompt:**
  > "'/Users/mac/USH/tikecting/image copy.png' tolong buat lebih prefisional dan clean ui, mungkin kita bisa '/Users/mac/USH/tikecting/image copy 2.png' kita akan buat uii tiket seperti itu tabelnya lebih clean dan bisa mengganti priority langsung, harusnya itu berada di segi admin it gimana ?"
- **Analisis & Keputusan Arsitektur Peran:**
  - **Admin IT (Level 1 / Helpdesk / Dispatcher):** Tepat sekali memegang tampilan "All Tickets" tabel clean seperti `image copy 2.png`. Admin IT melakukan triase awal tiket yang masuk dari seluruh kampus (mahasiswa, pengajar, karyawan), menilai/mengubah prioritas secara langsung, serta membagikan tiket ke teknisi/staff.
  - **Supervisor IT (Level 2 / Head of IT):** Berfokus pada supervisi beban kerja tim (monitoring progress staff %) dan persetujuan eskalasi tiket. Tampilan kartu kaku di `image copy.png` telah diubah menjadi dashboard modern dengan ringkasan Staff Progres di bagian atas dan tabel antrean clean.
- **Tindakan & Solusi:**
  - **Stat Cards Modern (Helpy Style):** Menambahkan 4 ringkasan card di atas tabel: Open Tickets, Pending Response (Needs Attention), In Progress, dan Staff Progres ringkas.
  - **Toolbar Filter Sleek:** Search bar terintegrasi, filter pill Status, Prioritas, Kategori Layanan, dan Staff/Agent dengan tombol Reset instan.
  - **Modern Clean Data Table:** Kolom Ticket ID (beserta tanggal request & delivered), Subject & preview dokumen lampiran, Customer avatar inisial, Agent assigned avatar + progress, Channel, Quick Inline Priority Changer, Status, dan Tombol Action.
  - **Quick Inline Priority Changer:** Menggunakan Alpine.js popover yang memungkinkan perubahan prioritas (Low, Medium, High, Urgent) secara langsung dengan 1 klik via AJAX Fetch tanpa reload halaman, dilengkapi feedback visual dot warna dan floating toast alert.
  - **Backend Updates:** Menambahkan filter priority dan staff di `TicketController@inbox`, perhitungan agregat statistik tiket, response JSON di `TicketController@updatePriority`, sinkronisasi nilai prioritas, serta izin penanganan tiket IT untuk Admin IT di model `Ticket`.
  - **Testing:** Menambahkan pengujian otomatis `test_clean_table_ui_supports_inline_priority_change_and_admin_it_triage` pada `TicketingHierarchyTest` (15 tests passed, 357 assertions).
- **File Terkait:**
  - `resources/views/tickets/inbox.blade.php`
  - `app/Http/Controllers/TicketController.php`
  - `app/Models/Ticket.php`
  - `tests/Feature/TicketingHierarchyTest.php`
  - `memory.md`
- **Status:** Selesai ✅

---

### [2026-10-23] Sesi Pembuatan Memori Proyek
- **User Prompt:**
  > "buatkan memory.md untuk menyimpan promt kita biat tidak hilang ingatan"
- **Tindakan & Solusi:**
  - Membuat `memory.md` sebagai basis data memori jangka panjang proyek ticketing.
  - Mengonfigurasi rule di `.gemini/rules/memory-rule.md` agar setiap sesi AI otomatis membaca dan memperbarui file ini.
  - Merangkum konteks arsitektur sistem tiket, role (Supervisor IT, Staff IT, Karyawan, dll), dan riwayat pengembangan sebelumnya.
- **File Terkait:**
  - `memory.md`
  - `.gemini/rules/memory-rule.md`
- **Status:** Selesai ✅

---

### [Riwayat Sebelumnya dari Git Commits]
- **Commit `6eb75b8`:** Menambahkan routing kategori IT ke Supervisor IT, bobot poin kerja (%), dan perhitungan progres otomatis untuk Staff IT.
- **Commit `e51ad91`:** Menambahkan sub-sidebar Karyawan di bawah Manajemen Pengguna dan seeder data dummy Admin IT, Staff, dan Supervisor IT.
- **Commit `b88c25b` & `992fa0b`:** Pembersihan UI Manajemen Menu dan Kategori Layanan agar lebih ringkas dan fleksibel.
- **Commit `7256d46`:** Custom service category hierarchy builder (Mahasiswa -> Akademik -> Yayasan) dan fitur upload/preview template dokumen.
- **Commit `dbaf008`:** Sinkronisasi data Mahasiswa & Pengajar dari REST API SIAKAD ke Manajemen Pengguna.

---

## 📌 Format Menambahkan Log Baru (Template)
Gunakan format berikut setiap kali mencatat prompt baru:

```markdown
### [YYYY-MM-DD] Judul Fitur / Perbaikan Singkat
- **User Prompt:**
  > "<Teks prompt user>"
- **Tindakan & Solusi:**
  - <Ringkasan apa yang dikerjakan / diselesaikan>
- **File Terkait:**
  - `<path/ke/file>`
- **Status:** <Selesai ✅ / Sedang Berjalan ⏳ / Tertunda ⏸️>
```

---

## 🚀 Daftar Tugas & Status Saat Ini (Roadmap / To-Do)
- [x] Inisialisasi `memory.md` dan aturan context retention
- [ ] Review perubahan file `resources/views/tickets/show.blade.php` (work in progress)
- [ ] Pengujian fitur perhitungan progres Staff IT & validasi Supervisor IT
- [ ] Verifikasi sinkronisasi SIAKAD REST API untuk data terbaru
