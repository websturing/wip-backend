<?php

namespace App\Features\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Lot extends Model
{
    use HasUuids;

    protected $fillable = ['gl_id', 'lot_number', 'lot_code', 'is_cancelled'];

    protected static function boot()
    {
        parent::boot();

        // Automatically set lot_code on creation and update if gl and lot_number are present
        static::saving(function ($model) {
            if ($model->glGroup && $model->lot_number) {
                $model->lot_code = $model->glGroup->gl_number . '-' . $model->lot_number;
            }
        });
    }

    public function glGroup()
    {
        return $this->belongsTo(GlGroup::class, 'gl_id');
    }
}
