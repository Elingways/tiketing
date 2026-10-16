<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'attachment_path',
        'attachment_name',
        'category_id',
        'priority',
        'workflow_type',
        'creator_id',
        'creator_role_id',
        'current_role_id',
        'assigned_user_id',
        'status',
        'approval_step',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function creatorRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'creator_role_id');
    }

    public function currentRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'current_role_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class)->orderBy('created_at', 'asc');
    }

    public function nextSuperiorRole(): ?Role
    {
        return $this->currentRole?->parent;
    }

    public function canBeHandledBy(User $user): bool
    {
        if (in_array($this->status, ['resolved', 'rejected', 'closed'], true)) {
            return false;
        }

        if ($user->isSuperuser()) {
            return true;
        }

        return (int) $user->role_id === (int) $this->current_role_id;
    }

    public function isAtFinalApprovalStage(): bool
    {
        $maxRoleId = $this->category?->max_approval_role_id;
        if ($maxRoleId && (int) $this->current_role_id === (int) $maxRoleId) {
            return true;
        }

        return $this->nextSuperiorRole() === null;
    }

    /**
     * Stepper visual hirarki yang akurat sesuai posisi terakhir tiket
     */
    public function getHierarchyStepper(): Collection
    {
        $steps = collect();
        if ($this->creatorRole) {
            $steps->push([
                'role' => $this->creatorRole,
                'label' => 'Pemohon',
                'state' => 'completed',
            ]);
        }

        $chain = $this->category?->getWorkflowChain($this->creatorRole) ?? collect();
        $foundCurrent = false;

        foreach ($chain as $index => $role) {
            $isCurrent = ((int) $role->id === (int) $this->current_role_id);

            if ($isCurrent) {
                $foundCurrent = true;
                if ($this->status === 'resolved') {
                    $state = 'completed';
                } elseif ($this->status === 'rejected') {
                    $state = 'rejected';
                } else {
                    $state = 'active';
                }
            } elseif (!$foundCurrent) {
                $state = 'completed';
            } else {
                $state = 'pending';
            }

            $steps->push([
                'role' => $role,
                'label' => $this->workflow_type === 'tiered_approval'
                    ? 'Tahap ' . ($index + 1)
                    : ($index === 0 ? 'Tujuan' : 'Eskalasi ' . $index),
                'state' => $state,
            ]);
        }

        return $steps;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'Baru Masuk',
            'in_progress' => 'Diproses',
            'escalated' => 'Dieskalasi',
            'waiting_next_approval' => 'Menunggu Approval',
            'resolved' => 'Selesai',
            'rejected' => 'Ditolak',
            'closed' => 'Ditutup',
            default => ucfirst($this->status),
        };
    }

    public function priorityLabel(): string
    {
        if (!$this->priority) {
            return 'Belum Diset';
        }

        return match ($this->priority) {
            'low' => 'Rendah',
            'medium' => 'Normal',
            'high' => 'Tinggi',
            'urgent' => 'Mendesak',
            default => ucfirst($this->priority),
        };
    }
}
