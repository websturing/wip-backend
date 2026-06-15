<?php

namespace App\Features\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeIdentity extends Model
{
    protected $table = 'employee_identities';

    protected $fillable = [
        'employee_id',
        'identity_type',
        'identity_number',
        'expiration_date',
        'document_path',
    ];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
