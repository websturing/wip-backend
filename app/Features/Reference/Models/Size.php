<?php

namespace App\Features\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Features\LayingPlanning\Models\LayingPlanning;

class Size extends Model
{
    use HasUuids;

    protected $fillable = ['size'];

    /**
     * Get the laying plannings associated with this size.
     */
    public function layingPlannings()
    {
        return $this->belongsToMany(LayingPlanning::class, 'laying_planning_sizes', 'size_id', 'laying_planning_id')
            ->withPivot('id', 'order_qty')
            ->withTimestamps();
    }
}
