<?php

namespace App\Features\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Customer extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'country'];

    public function glGroups()
    {
        return $this->hasMany(GlGroup::class);
    }
}
