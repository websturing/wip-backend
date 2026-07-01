<?php

namespace App\Features\LayingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LayingPlanningDetailType extends Model
{
    use HasUuids;

    protected $table = 'laying_planning_detail_types';

    protected $fillable = [
        'detail_type',
        'description',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(LayingPlanningDetail::class, 'laying_planning_detail_type_id');
    }
}
