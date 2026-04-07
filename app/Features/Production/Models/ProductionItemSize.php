<?php

namespace App\Features\Production\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionItemSize extends Model
{
    protected $fillable = ['production_item_id', 'size_name', 'qty_input', 'qty_output'];

    public function item()
    {
        return $this->belongsTo(ProductionItem::class, 'production_item_id');
    }
}
