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

    public function categories(): HasMany
    {
        return $this->hasMany(TicketCategory::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(DivisionMember::class);
    }

    /**
     * Urutan plot layanan: staff menerima, naik ke SPV, puncaknya Kepala.
     *
     * @return array<int, int>
     */
    public function workflowRoleIds(): array
    {
        return collect([
            (int) $this->staff_role_id,
            (int) $this->spv_role_id,
            (int) $this->head_role_id,
        ])->unique()->values()->all();
    }
}
