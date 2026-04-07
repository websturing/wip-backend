<?php

namespace App\Features\Production\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Features\Lines\Models\Line;

class Production extends Model
{
    protected $fillable = ['production_date', 'line_id', 'created_by', 'updated_by'];

    public function line()
    {
        return $this->belongsTo(Line::class, 'line_id');
    }

    public function items()
    {
        return $this->hasMany(ProductionItem::class, 'production_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
