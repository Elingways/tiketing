<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    protected $fillable = [
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'menu_role');
    }
}
