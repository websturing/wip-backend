<?php

namespace App\Features\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Fabric extends Model
{
    use HasUuids;

    protected $fillable = [
        'gl_id',
        'standard_content'
    ];

    public function glGroup()
    {
        return $this->belongsTo(GlGroup::class, 'gl_id');
    }

    public function aliases()
    {
        return $this->hasMany(FabricAlias::class, 'fabric_id');
    }
}
