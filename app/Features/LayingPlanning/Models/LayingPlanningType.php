<?php

namespace App\Features\LayingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LayingPlanningType extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'laying_planning_types';

    protected $fillable = [
        'type',
        'description',
    ];

    /**
     * Get the laying plannings associated with this type.
     */
    public function layingPlannings(): HasMany
    {
        return $this->hasMany(LayingPlanning::class, 'laying_planning_type_id');
    }
}
