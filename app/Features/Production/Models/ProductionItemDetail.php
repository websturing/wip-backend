<?php

namespace App\Features\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductionItemDetail extends Model
{
    use HasUuids;
    protected $fillable = ['production_item_id', 'size_name', 'qty_input', 'qty_output'];
}
