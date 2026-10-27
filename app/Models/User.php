<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'supervisor_id',
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
        'avatar_path',
        'synced_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'synced_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'creator_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_user_id');
    }

    public function headedRoles(): HasMany
    {
        return $this->hasMany(Role::class, 'head_user_id');
    }

    public function ticketLogs(): HasMany
    {
        return $this->hasMany(TicketLog::class, 'user_id');
    }

    public function doneProblems(): HasMany
    {
        return $this->hasMany(TicketProblem::class, 'done_by');
    }

    public function priorityRoutes(): HasMany
    {
        return $this->hasMany(RolePriorityRoute::class, 'user_id');
    }

    public function isSuperuser(): bool
    {
        return (bool) $this->role?->is_superuser;
    }

    public function isHeadOfRole(): bool
    {
        return (bool) ($this->role?->head_user_id && (int) $this->role->head_user_id === (int) $this->id);
    }

    public function isAdminIt(): bool
    {
        return in_array($this->role?->slug, ['admin-it', 'admin'], true);
    }

    public function isSupervisorIt(): bool
    {
        return $this->role?->slug === 'supervisor-it';
    }

    public function isStaff(): bool
    {
        return in_array($this->role?->slug, ['staff', 'staff-it'], true);
    }

    /**
     * Divisi tempat user bernaung
     */
    public function division(): ?Division
    {
        if ($this->role?->division_id) {
            return $this->role->division;
        }

        $member = DivisionMember::with('division')->where('user_id', $this->id)->first();
        return $member?->division;
    }

    /**
     * Cek apakah user adalah bagian dari pimpinan divisi (Kepala Divisi atau SPV)
     */
    public function isDivisionLeader(?Division $targetDivision = null): bool
    {
        if ($this->isSuperuser()) {
            return true;
        }

        $division = $targetDivision ?? $this->division();
        if (!$division) {
            return false;
        }

        $leadershipRoleIds = $division->leadershipRoleIds();
        if ($this->role_id && in_array((int) $this->role_id, $leadershipRoleIds, true)) {
            return true;
        }

        return DivisionMember::where('division_id', $division->id)
            ->where('user_id', $this->id)
            ->whereIn('position', ['head', 'spv'])
            ->exists();
    }

    /**
     * Ambil daftar menu dinamis yang berhak diakses oleh role user saat ini
     */
    public function accessibleMenus(): Collection
    {
        if (!$this->role) {
            return collect();
        }

        if ($this->isSuperuser()) {
            return Menu::with('children')->where('is_active', true)->orderBy('sort_order')->get();
        }

        return $this->role->menus()
            ->with('children')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $first = mb_substr($parts[0] ?? 'U', 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';
        return mb_strtoupper($first . $second);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar_path) {
            return route('users.avatar', [
                'user' => $this->id,
                'v' => optional($this->updated_at)->timestamp,
            ], false);
        }
        return null;
    }
}
