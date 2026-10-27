<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'badge_color',
        'description',
        'head_role_id',
        'spv_role_id',
        'staff_role_id',
    ];

    public function headRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'head_role_id');
    }

    public function spvRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'spv_role_id');
    }

    public function staffRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'staff_role_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class)->orderBy('level')->orderBy('name');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TicketCategory::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(DivisionMember::class);
    }

    /**
     * Role pimpinan di divisi ini (Kepala Divisi & Supervisor/SPV)
     *
     * @return \Illuminate\Support\Collection<int, Role>
     */
    public function leadershipRoles(): \Illuminate\Support\Collection
    {
        $roles = collect([$this->headRole, $this->spvRole])->filter();

        if ($this->relationLoaded('roles')) {
            $divRoles = $this->roles;
        } else {
            $divRoles = $this->roles()->get();
        }

        if ($roles->isEmpty() && $divRoles->isNotEmpty()) {
            $roles = $divRoles->filter(function ($r) {
                $lower = strtolower($r->name);
                return str_contains($lower, 'kepala') || str_contains($lower, 'head')
                    || str_contains($lower, 'spv') || str_contains($lower, 'supervisor');
            });
        }

        return $roles->unique('id')->values();
    }

    /**
     * @return array<int, int>
     */
    public function leadershipRoleIds(): array
    {
        return $this->leadershipRoles()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Seluruh pengguna berposisi staf dalam divisi ini
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function staffUsers(): \Illuminate\Support\Collection
    {
        $leadershipIds = $this->leadershipRoleIds();

        // 1. Staff dari role anggota divisi yang bukan pimpinan
        $roleUsers = User::whereHas('role', function ($q) use ($leadershipIds) {
            $q->where('division_id', $this->id);
            if (!empty($leadershipIds)) {
                $q->whereNotIn('id', $leadershipIds);
            }
        })->get();

        // 2. Staff dari division_user dengan position 'staff'
        $memberUsers = DivisionMember::with('user.role')
            ->where('division_id', $this->id)
            ->where('position', 'staff')
            ->get()
            ->pluck('user')
            ->filter();

        return $roleUsers->concat($memberUsers)->unique('id')->values();
    }

    /**
     * Urutan plot layanan: staff menerima, naik ke SPV, puncaknya Kepala.
     *
     * @return array<int, int>
     */
    public function workflowRoleIds(): array
    {
        $fixed = collect([
            $this->staff_role_id ? (int) $this->staff_role_id : null,
            $this->spv_role_id ? (int) $this->spv_role_id : null,
            $this->head_role_id ? (int) $this->head_role_id : null,
        ])->filter()->unique()->values()->all();

        if (!empty($fixed)) {
            return $fixed;
        }

        return $this->roles()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
