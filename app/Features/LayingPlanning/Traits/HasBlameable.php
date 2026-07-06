<?php

namespace App\Features\LayingPlanning\Traits;

use Illuminate\Support\Facades\Auth;

trait HasBlameable
{
    public static function bootHasBlameable(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by ??= auth()->id();
                $model->updated_by ??= auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()
                && method_exists($model, 'isForceDeleting')
                && !$model->isForceDeleting()
            ) {
                $model->deleted_by = auth()->id();
            }
        });
    }
}
