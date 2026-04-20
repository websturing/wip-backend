<?php

namespace App\Features\Productivity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Features\Lines\Models\Line;
use App\Features\Reference\Models\Lot;
use App\Features\Productivity\Models\ProductivityLot;

class Productivity extends Model
{
    use HasUuids;

    protected $fillable = [
        'line_id',
        'lot_id',
        'date',
        'manpower',
        'plan_manpower',
        'sewer',
        'plan_sewer',
        'working_hour',
        'target_plan',
        'smv',
        'last_step',
    ];

    protected $casts = [
        'date' => 'date',
        'manpower' => 'float',
        'plan_manpower' => 'float',
        'sewer' => 'float',
        'plan_sewer' => 'float',
        'working_hour' => 'float',
        'target_plan' => 'float',
        'smv' => 'float',
        'last_step' => 'float',
    ];

    public function line()
    {
        return $this->belongsTo(Line::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function lots()
    {
        return $this->belongsToMany(Lot::class, 'productivity_lots', 'productivity_id', 'lot_id')
                    ->using(ProductivityLot::class)
                    ->withPivot(['smv', 'last_step', 'target_plan', 'manpower', 'plan_manpower', 'sewer', 'plan_sewer', 'working_hour', 'media_id'])
                    ->withTimestamps();
    }

    public function productivityLots()
    {
        return $this->hasMany(ProductivityLot::class, 'productivity_id');
    }
}
