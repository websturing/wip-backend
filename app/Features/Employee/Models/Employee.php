<?php

namespace App\Features\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'phone',
        'address',
        'join_date',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(EmployeeIdentity::class);
    }
}
