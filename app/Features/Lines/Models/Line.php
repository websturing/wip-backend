<?php

namespace App\Features\Lines\Models;

use Illuminate\Database\Eloquent\Model;
use App\Features\Production\Models\Production;

class Line extends Model
{
    protected $fillable = ['name', 'location'];

    public function productions()
    {
        return $this->hasMany(Production::class, 'line_id');
    }
}
