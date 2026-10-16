<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class TicketCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'description',
        'initial_target_role_id',
        'workflow_type',
        'max_approval_role_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function initialTargetRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'initial_target_role_id');
    }

    public function maxApprovalRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'max_approval_role_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    /**
     * Tentukan role penerima pertama saat user dengan $creatorRole membuat tiket
     */
    public function resolveTargetRoleFor(Role $creatorRole): ?Role
    {
        if ($this->initial_target_role_id && $this->initialTargetRole) {
            return $this->initialTargetRole;
        }

        // Default: ikuti atasan langsung (parent_id) dari role pembuat tiket
        return $creatorRole->parent;
    }

    /**
     * Dapatkan daftar tahapan role untuk kategori ini mulai dari role tujuan awal ke atas
     */
    public function getWorkflowChain(?Role $creatorRole = null): Collection
    {
        $startRole = $this->initialTargetRole ?? $creatorRole?->parent;
        if (!$startRole) {
            return collect();
        }

        $chain = collect([$startRole]);
        foreach ($startRole->getAncestors() as $ancestor) {
            $chain->push($ancestor);
            if ($this->max_approval_role_id && $ancestor->id === $this->max_approval_role_id) {
                break;
            }
        }

        return $chain;
    }
}
