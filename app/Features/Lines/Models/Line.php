<?php

namespace App\Features\Lines\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Line extends Model
{
    use HasUuids;
    protected $fillable = ['name', 'location'];
}
