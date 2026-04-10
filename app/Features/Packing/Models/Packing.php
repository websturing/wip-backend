<?php

namespace App\Features\Packing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Packing extends Model
{
    use HasUuids;
    protected $fillable = [
        'packing_date',
        'man_power',
        'remarks'
    ];

    public function items()
    {
        return $this->hasMany(PackingItem::class);
    }
}
