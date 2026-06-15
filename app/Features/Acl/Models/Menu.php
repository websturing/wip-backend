<?php

namespace App\Features\Acl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'name',
        'path',
        'icon',
        'parent_id',
        'sort_order',
        'platform',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_order');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menus', 'menu_id', 'role_id');
    }
}
