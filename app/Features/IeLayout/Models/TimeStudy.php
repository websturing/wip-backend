<?php

namespace App\Features\IeLayout\Models;

use App\Features\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeStudy extends Model
{
    use HasFactory;

    protected $table = 'time_studies';

    protected $fillable = [
        'ie_layout_id',
        'operation_id',
        'section',
        'handling_position',
        'handling_position_value',
        'length',
        'sequence',
        'machine_type',
        'machine_turn',
        'man_power',
        'std_time',
        'target_hour',
        'target_day',
        'smv',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * Get the IE layout for this time study.
     */
    public function ieLayout(): BelongsTo
    {
        return $this->belongsTo(IeLayout::class, 'ie_layout_id');
    }

    /**
     * Get the operation associated with this time study.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }

    /**
     * Get the user who created this time study.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this time study.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
