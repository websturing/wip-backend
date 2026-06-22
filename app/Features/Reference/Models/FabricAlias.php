<?php

namespace App\Features\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FabricAlias extends Model
{
    use HasUuids;

    protected $fillable = [
        'fabric_id',
        'department',
        'alias_content'
    ];

    public function fabric()
    {
        return $this->belongsTo(Fabric::class, 'fabric_id');
    }
}
