<?php

namespace App\Features\LayingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Features\Reference\Models\Size;

class LayingPlanningSize extends Model
{
    use HasUuids;

    protected $table = 'laying_planning_sizes';

    protected $fillable = [
        'laying_planning_id',
        'size_id',
        'order_qty',
    ];

    /**
     * Get the laying planning associated with the size.
     */
    public function layingPlanning(): BelongsTo
    {
        return $this->belongsTo(LayingPlanning::class, 'laying_planning_id');
    }

    /**
     * Get the size.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class, 'size_id');
    }
}
