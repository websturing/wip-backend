<?php

namespace App\Features\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ColorAlias extends Model
{
    use HasUuids;

    protected $fillable = [
        'color_id',
        'department',
        'alias_name'
    ];

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }
}
