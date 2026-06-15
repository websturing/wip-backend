<?php

namespace App\Features\Employee\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityType extends Model
{
    protected $table = 'identity_types';

    protected $fillable = [
        'name',
        'is_expiration_required',
        'is_active',
    ];

    protected $casts = [
        'is_expiration_required' => 'boolean',
        'is_active' => 'boolean',
    ];
}
