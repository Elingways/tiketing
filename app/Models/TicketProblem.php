<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketProblem extends Model
{
    protected $fillable = [
        'ticket_id',
        'title',
        'weight_percent',
        'is_done',
        'done_at',
        'done_by',
        'sort_order',
    ];

    protected $casts = [
        'weight_percent' => 'integer',
        'is_done' => 'boolean',
        'done_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
