<?php

namespace App\Features\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Features\Reference\Models\Lot;

class ProductionItem extends Model
{
    use HasUuids;
    protected $fillable = ['production_id', 'lot_id', 'color', 'section', 'remarks'];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function details()
    {
        return $this->hasMany(ProductionItemDetail::class);
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }
}
