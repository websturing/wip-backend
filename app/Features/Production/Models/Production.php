<?php

namespace App\Features\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Features\Lines\Models\Line;

class Production extends Model
{
    use HasUuids;
    protected $fillable = [
        'line_id', 
        'production_date',
        'man_power_sewer',
        'man_power_matching',
        'man_power_qc',
        'man_power_others',
        'remarks'
    ];

    public function line()
    {
        return $this->belongsTo(Line::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionItem::class);
    }
}
