<?php

namespace App\Features\IeLayout\Models;

use App\Features\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operation extends Model
{
    use HasFactory;

    protected $table = 'operations';

    protected $fillable = [
        'name',
        'code',
        'sequence',
        'machine_type',
        'grade',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * Get the time studies associated with this operation.
     */
    public function timeStudies(): HasMany
    {
        return $this->hasMany(TimeStudy::class, 'operation_id');
    }

    /**
     * Get the user who created this operation.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this operation.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
