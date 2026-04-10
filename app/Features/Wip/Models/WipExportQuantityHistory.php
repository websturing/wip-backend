<?php

namespace App\Features\Wip\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\User;

class WipExportQuantityHistory extends Model
{
    use HasUuids;

    protected $fillable = ['export_quantity_id', 'old_qty', 'new_qty', 'updated_by'];

    public function exportQuantity()
    {
        return $this->belongsTo(WipExportQuantity::class, 'export_quantity_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
