<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'route_name',
        'icon',
        'color',
        'group',
        'badge_text',
        'description',
        'show_in_sidebar',
        'show_in_launcher',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'show_in_sidebar' => 'boolean',
        'show_in_launcher' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'menu_role');
    }

    public function resolveUrl(): string
    {
        $target = trim((string) $this->route_name);
        if ($target === '' || $target === '#') {
            return '#';
        }

        if (str_starts_with($target, 'http://') || str_starts_with($target, 'https://') || str_starts_with($target, '/')) {
            return $target;
        }

        if (\Illuminate\Support\Facades\Route::has($target)) {
            return route($target);
        }

        return '#';
    }

    public function isCurrentRoute(): bool
    {
        $target = trim((string) $this->route_name);
        if ($target === '' || $target === '#') {
            return false;
        }

        if (str_starts_with($target, '/')) {
            return request()->is(ltrim($target, '/'));
        }

        return request()->routeIs($target);
    }
}

