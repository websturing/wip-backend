<?php

namespace App\Features\Productivity\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Features\Media\Models\Media;

class ProductivityLot extends Pivot
{
    protected $table = 'productivity_lots';

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function lot()
    {
        return $this->belongsTo(\App\Features\Reference\Models\Lot::class, 'lot_id');
    }
}
