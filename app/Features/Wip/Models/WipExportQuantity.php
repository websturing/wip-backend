<?php

namespace App\Features\Wip\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\User;
use App\Features\Reference\Models\Lot;

class WipExportQuantity extends Model
{
    use HasUuids;

    protected $fillable = ['lot_id', 'qty', 'created_by', 'updated_by'];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories()
    {
        return $this->hasMany(WipExportQuantityHistory::class, 'export_quantity_id');
    }
}
