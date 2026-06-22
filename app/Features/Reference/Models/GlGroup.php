<?php

namespace App\Features\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GlGroup extends Model
{
    use HasUuids;

    protected $fillable = ['customer_id', 'gl_number'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lots()
    {
        return $this->hasMany(Lot::class, 'gl_id');
    }

    public function colors()
    {
        return $this->hasMany(Color::class, 'gl_id');
    }

    public function fabrics()
    {
        return $this->hasMany(Fabric::class, 'gl_id');
    }
}
