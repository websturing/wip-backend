<?php

namespace App\Features\IeLayout\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IeLayoutManpower extends Model
{
    protected $table = 'ie_layout_manpowers';

    protected $fillable = [
        'ie_layout_id',
        'date',
        'man_power_sewer',
        'man_power_matching',
        'man_power_qc',
        'man_power_others',
        'created_by_id',
    ];

    public function ieLayout(): BelongsTo
    {
        return $this->belongsTo(IeLayout::class);
    }
}
