<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

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
        'phone',
        'avatar_color',
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

    public function isSuperuser(): bool
    {
        return (bool) $this->role?->is_superuser;
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
}
