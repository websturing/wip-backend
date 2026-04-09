<?php

namespace App\Features\IeLayout\Models;

use App\Features\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IeLayout extends Model
{
    use HasFactory;

    protected $table = 'ie_layouts';

    protected $fillable = [
        'name',
        'lot_id',
        'price',
        'department',
        'total_smv',
        'man_power_sewer',
        'man_power_matching',
        'man_power_qc',
        'man_power_others',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * Get the lot associated with this layout.
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Reference\Models\Lot::class, 'lot_id');
    }

    /**
     * Get the time studies (details) for this layout.
     */
    public function details(): HasMany
    {
        return $this->hasMany(TimeStudy::class, 'ie_layout_id');
    }

    /**
     * Get the user who created this layout.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this layout.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
