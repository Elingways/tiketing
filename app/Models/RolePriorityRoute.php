<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePriorityRoute extends Model
{
    protected $fillable = [
        'role_id',
        'priority_level',
        'user_id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function labelFor(int $level): string
    {
        return match ($level) {
            1 => '1 · Urgent',
            2 => '2 · Agak urgent',
            3 => '3 · Vendor (jaringan / web)',
            4 => '4 · Bisa ditunda',
            default => $level . ' · Prioritas',
        };
    }

    public function levelLabel(): string
    {
        return self::labelFor((int) $this->priority_level);
    }

    public static function legacyPriority(int $level): string
    {
        return match (true) {
            $level === 1 => 'urgent',
            $level === 2 => 'high',
            $level === 3 => 'medium',
            $level === 4 => 'low',
            $level <= 2 => 'urgent',
            $level <= 4 => 'high',
            $level <= 6 => 'medium',
            default => 'low',
        };
    }
}
