<?php

namespace App\Features\LayingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Features\Reference\Models\Color;
use App\Features\Reference\Models\Fabric;
use App\Models\User;
use App\Features\LayingPlanning\Traits\HasBlameable;

class LayingPlanningDetailMaterial extends Model
{
    use HasUuids, HasBlameable;

    protected $table = 'laying_planning_detail_materials';

    protected $fillable = [
        'laying_planning_detail_id',
        'laying_planning_detail_type_id',
        'value_per_layer',
        'unit',
        'color_id',
        'fabric_id',
        'properties',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value_per_layer' => 'float',
        'properties' => 'array',
    ];

    public function layingPlanningDetail(): BelongsTo
    {
        return $this->belongsTo(LayingPlanningDetail::class, 'laying_planning_detail_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LayingPlanningDetailType::class, 'laying_planning_detail_type_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function fabric(): BelongsTo
    {
        return $this->belongsTo(Fabric::class, 'fabric_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
