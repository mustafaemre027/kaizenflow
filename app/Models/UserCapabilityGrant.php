<?php

namespace App\Models;

use App\Enums\UserCapability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCapabilityGrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'capability',
        'is_active',
        'granted_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capability' => UserCapability::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
