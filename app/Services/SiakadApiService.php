<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiakadApiService
{
    public function getApiUrl(): string
    {
        return rtrim((string) config('services.siakad.api_url', env('SIAKAD_API_URL', 'http://127.0.0.1:8090')), '/');
    }

    public function getApiKey(): string
    {
        return (string) config('services.siakad.api_key', env('SIAKAD_API_KEY', 'ush-secret-key-2026'));
    }

    public function getTimeout(): int
    {
        return (int) config('services.siakad.timeout', 30);
    }

    /**
     * Request GET ke REST API SIAKAD menggunakan X-SIAKAD-API-KEY = ush-secret-key-2026
     */
    public function getFromSiakad(string $path, array $query = []): array
    {
        $endpoint = $this->getApiUrl() . '/' . ltrim($path, '/');
        $apiKey = $this->getApiKey();

        $response = Http::timeout($this->getTimeout())
            ->withoutVerifying()
            ->withHeaders([
                'Accept' => 'application/json',
                'X-SIAKAD-API-KEY' => $apiKey,
                'X-API-Key' => $apiKey,
            ])
            ->get($endpoint, $query);

        if (!$response->successful()) {
            $msg = $response->json('message') ?? "HTTP {$response->status()}";
            throw new \RuntimeException("REST API SIAKAD [{$endpoint}] merespons error: {$msg}");
        }

        $payload = $response->json();
        if (!is_array($payload) || empty($payload['success'])) {
            throw new \RuntimeException($payload['message'] ?? "Format respons SIAKAD API tidak valid pada {$endpoint}");
        }

        return $payload['data'] ?? [];
    }

    /**
     * Ambil daftar Pengajar (Dosen, Kaprodi, Dekan) dari SIAKAD REST API
     */
    public function fetchLecturers(): array
    {
        return $this->getFromSiakad('/api/external/lecturers');
    }

    /**
     * Ambil daftar Mahasiswa dari SIAKAD REST API
     */
    public function fetchStudents(array $params = []): array
    {
        return $this->getFromSiakad('/api/external/students', $params);
    }

    /**
     * Sinkronisasi Data Pengajar (Dosen, Kaprodi, Dekan) dari SIAKAD REST API ke tabel users lokal
     */
    public function syncLecturers(): array
    {
        $rows = $this->fetchLecturers();

        $dosenRole = Role::where('slug', 'dosen')->first();
        $kaprodiRole = Role::where('slug', 'kaprodi')->first() ?? $dosenRole;
        $dekanRole = Role::where('slug', 'dekan')->first() ?? $kaprodiRole;

        $defaultPasswordHash = Hash::make('12345678');
        $created = 0;
        $updated = 0;

        foreach ($rows as $item) {
            $siakadId = (int) ($item['id_lecture'] ?? 0);
            $name = substr(trim((string) ($item['name'] ?? '')), 0, 180);
            $email = strtolower(substr(trim((string) ($item['email'] ?? '')), 0, 180));
            $rawNidn = trim((string) ($item['nidn'] ?? ''));
            if (str_starts_with($rawNidn, 'eyJ') || strlen($rawNidn) > 50 || $rawNidn === '-') {
                $rawNidn = 'NIDN-' . str_pad((string) $siakadId, 6, '0', STR_PAD_LEFT);
            }
            $nidn = $rawNidn;
            $prodi = substr(trim((string) ($item['program_studi'] ?? '-')), 0, 150);
            $fakultas = substr(trim((string) ($item['fakultas'] ?? '-')), 0, 150);
            $jabatan = substr(trim((string) ($item['jabatan'] ?? 'Dosen')), 0, 150);
            $roleSlug = strtolower(trim((string) ($item['role_slug'] ?? 'dosen')));

            if ($name === '' || str_starts_with($name, 'eyJ') || $email === '') {
                continue;
            }

            $targetRole = match ($roleSlug) {
                'dekan' => $dekanRole,
                'kaprodi' => $kaprodiRole,
                default => $dosenRole,
            };

            $avatarColor = match ($roleSlug) {
                'dekan' => 'violet',
                'kaprodi' => 'amber',
                default => 'emerald',
            };

            $existing = null;
            if ($siakadId > 0) {
                $existing = User::where('user_type', 'pengajar')
                    ->where('siakad_id', $siakadId)
                    ->first();
            }
            if (!$existing) {
                $existing = User::where('email', $email)->first();
            }

            if ($existing) {
                // Pastikan email tidak bentrok jika berubah
                $emailConflict = User::where('email', $email)->where('id', '!=', $existing->id)->exists();
                $safeEmail = $emailConflict ? $existing->email : $email;

                $existing->update([
                    'name' => $name,
                    'email' => $safeEmail,
                    'user_type' => 'pengajar',
                    'siakad_id' => $siakadId ?: $existing->siakad_id,
                    'identity_number' => $nidn ?: $existing->identity_number,
                    'department' => $prodi !== '-' ? $prodi : $existing->department,
                    'fakultas' => $fakultas !== '-' ? $fakultas : $existing->fakultas,
                    'jabatan' => $jabatan ?: $existing->jabatan,
                    'role_id' => $existing->role_id ?: $targetRole?->id,
                    'synced_at' => now(),
                ]);
                $updated++;
            } else {
                $safeEmail = $email;
                if (User::where('email', $safeEmail)->exists()) {
                    $safeEmail = 'pengajar.' . ($siakadId ?: rand(1000, 9999)) . '.' . $email;
                }

                User::create([
                    'name' => $name,
                    'email' => $safeEmail,
                    'password' => $defaultPasswordHash,
                    'role_id' => $targetRole?->id,
                    'user_type' => 'pengajar',
                    'siakad_id' => $siakadId ?: null,
                    'identity_number' => $nidn ?: null,
                    'department' => $prodi !== '-' ? $prodi : null,
                    'fakultas' => $fakultas !== '-' ? $fakultas : null,
                    'status_akademik' => 'Aktif',
                    'jabatan' => $jabatan,
                    'avatar_color' => $avatarColor,
                    'synced_at' => now(),
                ]);
                $created++;
            }
        }

        // Hubungkan otomatis hirarki atasan pengajar (Dosen -> Kaprodi Prodi terkait, Kaprodi -> Dekan Fakultas terkait)
        $this->linkLecturerSupervisors($dosenRole, $kaprodiRole, $dekanRole);

        return [
            'total_api' => count($rows),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Hubungkan relasi supervisor_id antar Pengajar sesuai Prodi & Fakultas
     */
    private function linkLecturerSupervisors(?Role $dosenRole, ?Role $kaprodiRole, ?Role $dekanRole): void
    {
        if (!$dosenRole || !$kaprodiRole) {
            return;
        }

        $dekanUsers = $dekanRole
            ? User::where('role_id', $dekanRole->id)->get()
            : collect();
        $defaultDekan = $dekanUsers->first();

        $kaprodiUsers = User::where('role_id', $kaprodiRole->id)->get();
        $kaprodiByProdi = [];
        foreach ($kaprodiUsers as $kp) {
            if ($kp->department) {
                $kaprodiByProdi[strtolower(trim($kp->department))] = $kp;
            }
            if (!$kp->supervisor_id && $defaultDekan) {
                $matchedDekan = $dekanUsers->first(fn ($d) => $d->fakultas && $kp->fakultas && strtolower($d->fakultas) === strtolower($kp->fakultas))
                    ?? $defaultDekan;
                $kp->update(['supervisor_id' => $matchedDekan->id]);
            }
        }

        $defaultKaprodi = $kaprodiUsers->first();
        $dosenUsers = User::where('role_id', $dosenRole->id)->where('user_type', 'pengajar')->get();

        foreach ($dosenUsers as $ds) {
            if (!$ds->supervisor_id) {
                $prodiKey = strtolower(trim((string) $ds->department));
                $matchedKaprodi = ($prodiKey !== '' && isset($kaprodiByProdi[$prodiKey]))
                    ? $kaprodiByProdi[$prodiKey]
                    : $defaultKaprodi;

                if ($matchedKaprodi && $matchedKaprodi->id !== $ds->id) {
                    $ds->update(['supervisor_id' => $matchedKaprodi->id]);
                }
            }
        }
    }

    /**
     * Sinkronisasi Data Mahasiswa dari SIAKAD REST API ke tabel users lokal
     */
    public function syncStudents(int $limit = 1500): array
    {
        $rows = $this->fetchStudents(['limit' => $limit]);

        $mahasiswaRole = Role::where('slug', 'mahasiswa')->first();
        $defaultPasswordHash = Hash::make('12345678');

        // Peta pengajar berdasarkan siakad_id (id_lecture) untuk mencocokkan Dosen PA
        $lecturersBySiakadId = User::where('user_type', 'pengajar')
            ->whereNotNull('siakad_id')
            ->pluck('id', 'siakad_id');

        $defaultAdvisorId = User::where('user_type', 'pengajar')->value('id');

        $created = 0;
        $updated = 0;

        foreach ($rows as $item) {
            $siakadId = (int) ($item['id_mahasiswa'] ?? 0);
            $nim = trim((string) ($item['nim'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            $email = strtolower(trim((string) ($item['email'] ?? '')));
            $prodi = trim((string) ($item['program_studi'] ?? '-'));
            $fakultas = trim((string) ($item['fakultas'] ?? '-'));
            $angkatan = (int) ($item['angkatan'] ?? 2024);
            $semester = (int) ($item['semester'] ?? 1);
            $status = trim((string) ($item['status_mahasiswa'] ?? 'Aktif'));
            $phone = trim((string) ($item['phone'] ?? ''));
            $advisorSiakadId = !empty($item['id_dosen_pembimbing']) ? (int) $item['id_dosen_pembimbing'] : null;

            if ($nim === '' || $name === '') {
                continue;
            }

            if ($email === '' || !str_contains($email, '@')) {
                $email = strtolower($nim) . '@mhs.sugenghartono.ac.id';
            }

            $supervisorId = ($advisorSiakadId && isset($lecturersBySiakadId[$advisorSiakadId]))
                ? $lecturersBySiakadId[$advisorSiakadId]
                : $defaultAdvisorId;

            $existing = User::where('user_type', 'mahasiswa')
                ->where(function ($q) use ($nim, $siakadId) {
                    $q->where('identity_number', $nim);
                    if ($siakadId > 0) {
                        $q->orWhere('siakad_id', $siakadId);
                    }
                })
                ->first();

            if (!$existing) {
                $existing = User::where('email', $email)->first();
            }

            if ($existing) {
                $emailConflict = User::where('email', $email)->where('id', '!=', $existing->id)->exists();
                $safeEmail = $emailConflict ? $existing->email : $email;

                $existing->update([
                    'name' => $name,
                    'email' => $safeEmail,
                    'role_id' => $existing->role_id ?: $mahasiswaRole?->id,
                    'supervisor_id' => $supervisorId ?: $existing->supervisor_id,
                    'user_type' => 'mahasiswa',
                    'siakad_id' => $siakadId ?: $existing->siakad_id,
                    'identity_number' => $nim,
                    'department' => $prodi !== '-' ? $prodi : $existing->department,
                    'fakultas' => $fakultas !== '-' ? $fakultas : $existing->fakultas,
                    'angkatan' => $angkatan,
                    'semester' => $semester,
                    'status_akademik' => $status,
                    'phone' => $phone ?: $existing->phone,
                    'synced_at' => now(),
                ]);
                $updated++;
            } else {
                $safeEmail = $email;
                if (User::where('email', $safeEmail)->exists()) {
                    $safeEmail = strtolower($nim) . '@mhs.sugenghartono.ac.id';
                    if (User::where('email', $safeEmail)->exists()) {
                        $safeEmail = strtolower($nim) . '.' . ($siakadId ?: rand(100, 999)) . '@mhs.sugenghartono.ac.id';
                    }
                }

                User::create([
                    'name' => $name,
                    'email' => $safeEmail,
                    'password' => $defaultPasswordHash,
                    'role_id' => $mahasiswaRole?->id,
                    'supervisor_id' => $supervisorId,
                    'user_type' => 'mahasiswa',
                    'siakad_id' => $siakadId ?: null,
                    'identity_number' => $nim,
                    'department' => $prodi !== '-' ? $prodi : 'Informatika',
                    'fakultas' => $fakultas !== '-' ? $fakultas : null,
                    'angkatan' => $angkatan,
                    'semester' => $semester,
                    'status_akademik' => $status,
                    'phone' => $phone ?: null,
                    'avatar_color' => 'indigo',
                    'synced_at' => now(),
                ]);
                $created++;
            }
        }

        return [
            'total_api' => count($rows),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Sinkronisasi penuh Pengajar + Mahasiswa dari SIAKAD REST API
     */
    public function syncAll(): array
    {
        $lecturers = $this->syncLecturers();
        $students = $this->syncStudents();

        return [
            'lecturers' => $lecturers,
            'students' => $students,
        ];
    }
}
