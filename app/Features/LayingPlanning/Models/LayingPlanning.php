<?php

namespace App\Features\LayingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Features\Reference\Models\Lot;
use App\Features\Reference\Models\Color;
use App\Features\Reference\Models\Fabric;
use App\Features\Reference\Models\Size;

class LayingPlanning extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'laying_plannings';

    protected $fillable = [
        'serial_number',
        'lot_id',
        'laying_planning_type_id',
        'laying_planning_parent_id',
        'color_id',
        'fabric_id',
        'plan_date',
        'fabric_pattern',
        'is_combine',
        'laying_planning_combine_id',
        'is_set_item',
    ];

    protected $casts = [
        'is_combine' => 'boolean',
        'is_set_item' => 'boolean',
        'plan_date' => 'date',
    ];

    /**
     * Get the lot associated with the laying planning.
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    /**
     * Get the parent laying planning.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(LayingPlanning::class, 'laying_planning_parent_id');
    }

    /**
     * Get the secondary support planning records (children).
     */
    public function children(): HasMany
    {
        return $this->hasMany(LayingPlanning::class, 'laying_planning_parent_id');
    }

    /**
     * Get the laying planning type associated with the laying planning.
     */
    public function layingPlanningType(): BelongsTo
    {
        return $this->belongsTo(LayingPlanningType::class, 'laying_planning_type_id');
    }

    /**
     * Get the color associated with the laying planning.
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    /**
     * Get the fabric associated with the laying planning.
     */
    public function fabric(): BelongsTo
    {
        return $this->belongsTo(Fabric::class, 'fabric_id');
    }

    /**
     * Get the sizes associated with the laying planning.
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(LayingPlanningSize::class, 'laying_planning_id');
    }

    /**
     * Get the size details associated with the laying planning.
     */
    public function sizeDetails()
    {
        return $this->belongsToMany(Size::class, 'laying_planning_sizes', 'laying_planning_id', 'size_id')
            ->withPivot('id', 'order_qty')
            ->withTimestamps();
    }

    /**
     * Get the combine group associated with this planning.
     */
    public function combineGroup(): BelongsTo
    {
        return $this->belongsTo(LayingPlanningCombine::class, 'laying_planning_combine_id');
    }

    /**
     * Get the parts associated with this planning.
     */
    public function parts(): HasMany
    {
        return $this->hasMany(LayingPlanningPart::class, 'laying_planning_id');
    }
}



