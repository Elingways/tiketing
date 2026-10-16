<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'from_role_id',
        'to_role_id',
        'action',
        'note',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'from_role_id');
    }

    public function toRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'to_role_id');
    }

    public function actionBadge(): array
    {
        return match ($this->action) {
            'created' => ['label' => 'Tiket Dibuat', 'color' => 'indigo'],
            'in_progress' => ['label' => 'Mulai Diproses', 'color' => 'sky'],
            'escalated' => ['label' => 'Eskalasi Hirarki Naik', 'color' => 'amber'],
            'disposed' => ['label' => 'Disposisi ke Bawahan', 'color' => 'violet'],
            'approved_step' => ['label' => 'Disetujui & Naik Hirarki', 'color' => 'emerald'],
            'final_approved' => ['label' => 'Approval Final Selesai', 'color' => 'emerald'],
            'resolved' => ['label' => 'Tiket Diselesaikan', 'color' => 'emerald'],
            'rejected' => ['label' => 'Tiket Ditolak', 'color' => 'rose'],
            'comment' => ['label' => 'Balasan / Diskusi', 'color' => 'slate'],
            default => ['label' => ucfirst($this->action), 'color' => 'slate'],
        };
    }
}
