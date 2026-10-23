---
description: Aturan otomatis untuk membaca dan memperbarui memory.md di setiap sesi
globs: *
---

# Aturan Pengelolaan Memori Proyek (memory.md)

1. **Selalu Baca `memory.md`:**
   Sebelum memulai pengerjaan fitur baru atau memberikan saran arsitektur, rujuk konteks yang tercatat di `memory.md`.

2. **Perbarui `memory.md` Secara Berkala:**
   Setiap kali ada prompt, perubahan fitur signifikan, perbaikan bug, atau penambahan endpoint/modul baru:
   - Tambahkan riwayat prompt dan solusinya ke bagian **Riwayat Log Prompt & Solusi** di `memory.md`.
   - Update checklist pada bagian **Daftar Tugas & Status Saat Ini** jika ada item yang selesai atau baru ditambahkan.

3. **Pertahankan Logika Bisnis:**
   Perhatikan aturan bisnis utama (routing tiket IT ke Supervisor IT, bobot poin %, integrasi SIAKAD REST API, dan hirarki approval dinamis) agar tidak rusak saat melakukan refactor.
