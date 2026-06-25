<?php

namespace App\Features\LayingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayingPlanningPart extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'laying_planning_parts';

    protected $fillable = [
        'laying_planning_id',
        'item_part',
        'item_part_group_code',
    ];

    /**
     * Get the laying planning associated with this part.
     */
    public function layingPlanning(): BelongsTo
    {
        return $this->belongsTo(LayingPlanning::class, 'laying_planning_id');
    }
}
