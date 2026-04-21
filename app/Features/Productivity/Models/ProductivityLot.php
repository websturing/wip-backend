<?php

namespace App\Features\Productivity\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Features\Media\Models\Media;

class ProductivityLot extends Pivot
{
    protected $table = 'productivity_lots';

    protected $fillable = [
        'productivity_id',
        'lot_id',
        'smv',
        'last_step',
        'target_plan',
        'manpower',
        'plan_manpower',
        'sewer',
        'plan_sewer',
        'working_hour',
        'media_id',
        'section'
    ];

    protected $with = ['media'];

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function lot()
    {
        return $this->belongsTo(\App\Features\Reference\Models\Lot::class, 'lot_id');
    }

    public function toArray()
    {
        $array = parent::toArray();
        if ($this->relationLoaded('media')) {
            $array['media'] = $this->media;
        }
        return $array;
    }
}
