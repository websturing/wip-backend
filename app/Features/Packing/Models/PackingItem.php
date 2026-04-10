<?php

namespace App\Features\Packing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Features\Reference\Models\Lot;

class PackingItem extends Model
{
    use HasUuids;
    protected $fillable = ['packing_id', 'lot_id', 'color'];

    public function packing()
    {
        return $this->belongsTo(Packing::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function details()
    {
        return $this->hasMany(PackingItemDetail::class);
    }
}
