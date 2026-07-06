<?php

namespace App\Features\LayingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Features\LayingPlanning\Traits\HasBlameable;

class LayingPlanningDetail extends Model
{
    use HasUuids, SoftDeletes, HasBlameable;

    protected $table = 'laying_planning_details';

    protected $fillable = [
        'laying_planning_id',
        'laying_planning_detail_type_id',
        'table_number',
        'layer_qty',
        'marker_code',
        'marker_yard',
        'marker_inch',
        'allowance_inch',
        'is_pilot_run',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'marker_inch' => 'float',
        'allowance_inch' => 'float',
        'is_pilot_run' => 'boolean',
    ];

    public function getMarkerLengthAttribute(): float
    {
        return round(
            $this->marker_yard + ($this->marker_inch / 36) + ($this->allowance_inch / 36),
            3
        );
    }

    public function getTotalLengthAttribute(): float
    {
        return round($this->layer_qty * $this->marker_length, 3);
    }

    public function layingPlanning(): BelongsTo
    {
        return $this->belongsTo(LayingPlanning::class, 'laying_planning_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LayingPlanningDetailType::class, 'laying_planning_detail_type_id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(LayingPlanningDetailSize::class, 'laying_planning_detail_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LayingPlanningDetailMaterial::class, 'laying_planning_detail_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
