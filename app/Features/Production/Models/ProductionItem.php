<?php

namespace App\Features\Production\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionItem extends Model
{
    protected $fillable = ['production_id', 'gl_number', 'color'];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function sizes()
    {
        return $this->hasMany(ProductionItemSize::class);
    }
}
