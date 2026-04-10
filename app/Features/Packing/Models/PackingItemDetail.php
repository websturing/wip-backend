<?php

namespace App\Features\Packing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PackingItemDetail extends Model
{
    use HasUuids;
    protected $fillable = ['packing_item_id', 'size_name', 'qty_input', 'qty_output'];
}
