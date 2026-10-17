<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\SiakadApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.users.students');
    }

    /**
     * Sub-Sidebar 1: Management Mahasiswa (Data ditarik dari REST API SIAKAD)
     */
    public function students(Request $request, SiakadApiService $siakadService)
    {
        // Auto-sync pertama kali dari REST API SIAKAD jika belum ada mahasiswa ber-siakad_id
        $autoSyncError = null;
        if (!app()->runningUnitTests() && User::where('user_type', 'mahasiswa')->whereNotNull('siakad_id')->count() === 0) {
            try {
                $siakadService->syncAll();
            } catch (\Throwable $e) {
                $autoSyncError = $e->getMessage();
                Log::warning('Auto-sync SIAKAD Mahasiswa tertunda: ' . $e->getMessage());
            }
        }

        $search = trim((string) $request->input('search', ''));
        $programStudi = trim((string) $request->input('program_studi', ''));
        $angkatan = trim((string) $request->input('angkatan', ''));
        $statusAkademik = trim((string) $request->input('status_akademik', ''));

        $mahasiswaRole = Role::where('slug', 'mahasiswa')->first();

        $baseQuery = User::where(function ($q) use ($mahasiswaRole) {
            $q->where('user_type', 'mahasiswa');
            if ($mahasiswaRole) {
                $q->orWhere('role_id', $mahasiswaRole->id);
            }
        });

        $totalStudents = (clone $baseQuery)->count();
        $activeStudentCount = (clone $baseQuery)->whereIn('status_akademik', ['Aktif', 'Mahasiswa baru'])->count();
        $withAdvisorCount = (clone $baseQuery)->whereNotNull('supervisor_id')->count();

        $programOptions = (clone $baseQuery)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->where('department', '!=', '-')
            ->select('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $angkatanOptions = (clone $baseQuery)
            ->whereNotNull('angkatan')
            ->select('angkatan')
            ->distinct()
            ->orderByDesc('angkatan')
            ->pluck('angkatan');

        $query = (clone $baseQuery)->with(['role', 'supervisor.role'])->withCount('createdTickets');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identity_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($programStudi !== '') {
            $query->where('department', $programStudi);
        }

        if ($angkatan !== '') {
            $query->where('angkatan', (int) $angkatan);
        }

        if ($statusAkademik !== '') {
            $query->where('status_akademik', $statusAkademik);
        }

        $students = $query
            ->orderByDesc('angkatan')
            ->orderBy('identity_number')
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        $lecturers = User::with('role')
            ->where('user_type', 'pengajar')
            ->orderBy('name')
            ->get();

        $lastSyncedAt = User::where('user_type', 'mahasiswa')->max('synced_at');

        return view('admin.users.students', compact(
            'students',
            'lecturers',
            'mahasiswaRole',
            'search',
            'programStudi',
            'angkatan',
            'statusAkademik',
            'programOptions',
            'angkatanOptions',
            'totalStudents',
            'activeStudentCount',
            'withAdvisorCount',
            'lastSyncedAt',
            'autoSyncError'
        ));
    }

    /**
     * Sub-Sidebar 2: Management Pengajar (Dosen, Kaprodi, Dekan & Role Hirarki Lainnya)
     */
    public function lecturers(Request $request, SiakadApiService $siakadService)
    {
        $autoSyncError = null;
        if (!app()->runningUnitTests() && User::where('user_type', 'pengajar')->whereNotNull('siakad_id')->count() === 0) {
            try {
                $siakadService->syncLecturers();
            } catch (\Throwable $e) {
                $autoSyncError = $e->getMessage();
                Log::warning('Auto-sync SIAKAD Pengajar tertunda: ' . $e->getMessage());
            }
        }

        $search = trim((string) $request->input('search', ''));
        $programStudi = trim((string) $request->input('program_studi', ''));
        $roleFilter = $request->input('role_id');

        $mahasiswaRole = Role::where('slug', 'mahasiswa')->first();
        $lecturerRoles = Role::where('slug', '!=', 'mahasiswa')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $baseQuery = User::where(function ($q) use ($mahasiswaRole) {
            $q->where('user_type', 'pengajar');
            if ($mahasiswaRole) {
                $q->orWhere(function ($sq) use ($mahasiswaRole) {
                    $sq->where('role_id', '!=', $mahasiswaRole->id)
                        ->where('user_type', '!=', 'mahasiswa');
                });
            }
        });

        $totalLecturers = (clone $baseQuery)->count();
        $dosenCount = (clone $baseQuery)->whereHas('role', fn ($q) => $q->where('slug', 'dosen'))->count();
        $kaprodiCount = (clone $baseQuery)->whereHas('role', fn ($q) => $q->where('slug', 'kaprodi'))->count();
        $dekanCount = (clone $baseQuery)->whereHas('role', fn ($q) => $q->where('slug', 'dekan'))->count();

        $programOptions = (clone $baseQuery)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->where('department', '!=', '-')
            ->select('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $query = (clone $baseQuery)
            ->with(['role.parent', 'supervisor.role'])
            ->withCount(['subordinates as students_count' => function ($q) {
                $q->where('user_type', 'mahasiswa');
            }]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identity_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        if ($programStudi !== '') {
            $query->where('department', $programStudi);
        }

        if ($roleFilter) {
            $query->where('role_id', $roleFilter);
        }

        $lecturers = $query
            ->orderBy('role_id')
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        $allPotentialSupervisors = (clone $baseQuery)->with('role')->orderBy('role_id')->orderBy('name')->get();
        $lastSyncedAt = User::where('user_type', 'pengajar')->max('synced_at');

        return view('admin.users.lecturers', compact(
            'lecturers',
            'lecturerRoles',
            'allPotentialSupervisors',
            'search',
            'programStudi',
            'roleFilter',
            'programOptions',
            'totalLecturers',
            'dosenCount',
            'kaprodiCount',
            'dekanCount',
            'lastSyncedAt',
            'autoSyncError'
        ));
    }

    /**
     * Tarik / Sinkronisasi Data Mahasiswa & Pengajar dari REST API SIAKAD (SIAKAD_API_KEY=ush-secret-key-2026)
     */
    public function syncFromSiakad(Request $request, SiakadApiService $siakadService)
    {
        $type = $request->input('type', 'all');

        try {
            if ($type === 'lecturers') {
                $res = $siakadService->syncLecturers();
                return back()->with(
                    'success',
                    "Sinkronisasi REST API SIAKAD berhasil! {$res['total_api']} data Pengajar (Dosen, Kaprodi, Dekan) ditarik ({$res['created']} baru, {$res['updated']} diperbarui)."
                );
            }

            if ($type === 'students') {
                // Pastikan pengajar disinkronkan dulu agar relasi Dosen PA terhubung akurat
                $lecRes = $siakadService->syncLecturers();
                $stdRes = $siakadService->syncStudents();
                return back()->with(
                    'success',
                    "Sinkronisasi REST API SIAKAD berhasil! {$stdRes['total_api']} Mahasiswa ditarik ({$stdRes['created']} baru, {$stdRes['updated']} diperbarui) dan terhubung ke {$lecRes['total_api']} Pengajar."
                );
            }

            $all = $siakadService->syncAll();
            return back()->with(
                'success',
                "Sinkronisasi Penuh REST API SIAKAD berhasil! Pengajar: {$all['lecturers']['total_api']} data, Mahasiswa: {$all['students']['total_api']} data."
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menarik data dari REST API SIAKAD: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:4'],
            'role_id' => ['required', 'exists:roles,id'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'user_type' => ['nullable', 'in:mahasiswa,pengajar,admin'],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:120'],
            'fakultas' => ['nullable', 'string', 'max:150'],
            'angkatan' => ['nullable', 'integer'],
            'semester' => ['nullable', 'integer'],
            'status_akademik' => ['nullable', 'string', 'max:40'],
            'jabatan' => ['nullable', 'string', 'max:120'],
        ]);

        $role = Role::find($validated['role_id']);
        $userType = $validated['user_type'] ?? ($role?->slug === 'mahasiswa' ? 'mahasiswa' : 'pengajar');

        User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password'] ?? '12345678'),
            'role_id' => $validated['role_id'],
            'supervisor_id' => $validated['supervisor_id'] ?: null,
            'user_type' => $userType,
            'identity_number' => $validated['identity_number'] ?? null,
            'department' => $validated['department'] ?? null,
            'fakultas' => $validated['fakultas'] ?? null,
            'angkatan' => $validated['angkatan'] ?? null,
            'semester' => $validated['semester'] ?? null,
            'status_akademik' => $validated['status_akademik'] ?? 'Aktif',
            'jabatan' => $validated['jabatan'] ?? ($role?->name ?? 'Dosen'),
            'avatar_color' => $role?->badge_color ?? 'indigo',
        ]);

        return back()->with('success', "Data '{$validated['name']}' berhasil ditambahkan dengan role {$role?->name}!");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:120'],
            'angkatan' => ['nullable', 'integer'],
            'semester' => ['nullable', 'integer'],
            'status_akademik' => ['nullable', 'string', 'max:40'],
            'jabatan' => ['nullable', 'string', 'max:120'],
        ]);

        $role = Role::find($validated['role_id']);

        $user->update([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role_id' => $validated['role_id'],
            'supervisor_id' => $validated['supervisor_id'] ?: null,
            'identity_number' => $validated['identity_number'] ?? $user->identity_number,
            'department' => $validated['department'] ?? $user->department,
            'angkatan' => $validated['angkatan'] ?? $user->angkatan,
            'semester' => $validated['semester'] ?? $user->semester,
            'status_akademik' => $validated['status_akademik'] ?? $user->status_akademik,
            'jabatan' => $validated['jabatan'] ?? $user->jabatan,
            'avatar_color' => $role?->badge_color ?? $user->avatar_color,
        ]);

        return back()->with('success', "Data & Role '{$user->name}' ({$role?->name}) berhasil diperbarui!");
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('12345678'),
        ]);

        return back()->with('success', "Password untuk '{$user->name}' berhasil direset ke default: 12345678.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan login.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Data '{$name}' berhasil dihapus.");
    }
}
