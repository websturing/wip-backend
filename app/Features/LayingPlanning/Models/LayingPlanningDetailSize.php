<?php

namespace App\Features\LayingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Features\Reference\Models\Size;

class LayingPlanningDetailSize extends Model
{
    use HasUuids;

    protected $table = 'laying_planning_detail_sizes';

    protected $fillable = [
        'laying_planning_detail_id',
        'size_id',
        'ratio_per_size',
    ];

    public function layingPlanningDetail(): BelongsTo
    {
        return $this->belongsTo(LayingPlanningDetail::class, 'laying_planning_detail_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class, 'size_id');
    }
}
