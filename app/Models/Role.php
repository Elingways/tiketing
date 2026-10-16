<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'level',
        'badge_color',
        'icon',
        'description',
        'is_superuser',
        'can_create_ticket',
        'can_receive_ticket',
    ];

    protected $casts = [
        'is_superuser' => 'boolean',
        'can_create_ticket' => 'boolean',
        'can_receive_ticket' => 'boolean',
    ];

    /**
     * Role atasan langsung (Parent di dalam hirarki, misal Mahasiswa -> Dosen -> Kaprodi)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    /**
     * Role bawahan langsung (Children di dalam hirarki)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Role::class, 'parent_id')->orderBy('name');
    }

    /**
     * Rekursif seluruh bawahan untuk visualisasi pohon hirarki
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with(['childrenRecursive', 'users']);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_role');
    }

    public function incomingTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'current_role_id');
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'creator_role_id');
    }

    /**
     * Dapatkan urutan rantai atasan dari role ini ke atas (misal: [Dosen, Kaprodi, Dekan])
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $visited = [$this->id];
        $current = $this->parent;

        while ($current && !in_array($current->id, $visited, true)) {
            $ancestors->push($current);
            $visited[] = $current->id;
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Dapatkan teks alur hirarki ke atas (contoh: "Mahasiswa → Dosen → Kaprodi → Dekan")
     */
    public function getUpwardChainNames(): array
    {
        $chain = [$this->name];
        foreach ($this->getAncestors() as $ancestor) {
            $chain[] = $ancestor->name;
        }
        return $chain;
    }

    /**
     * Cek apakah menjadikan $candidateParentId sebagai parent_id akan menyebabkan circular loop
     */
    public function wouldCreateCycle(?int $candidateParentId): bool
    {
        if (!$candidateParentId) {
            return false;
        }
        if ($candidateParentId === $this->id) {
            return true;
        }

        $visited = [$this->id];
        $current = self::find($candidateParentId);

        while ($current) {
            if (in_array($current->id, $visited, true)) {
                return true;
            }
            $visited[] = $current->id;
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Rekalkulasi level kedalaman seluruh pohon role
     */
    public static function recalculateLevels(): void
    {
        $allRoles = self::with('parent')->get();
        foreach ($allRoles as $role) {
            $depth = 0;
            $visited = [$role->id];
            $curr = $role->parent;
            while ($curr && !in_array($curr->id, $visited, true)) {
                $depth++;
                $visited[] = $curr->id;
                $curr = $curr->parent;
            }
            $role->updateQuietly(['level' => $depth]);
        }
    }
}
