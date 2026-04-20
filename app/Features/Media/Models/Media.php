<?php

namespace App\Features\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasUuids;

    protected $fillable = [
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'collection_name',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset(Storage::url($this->file_path));
    }
}
