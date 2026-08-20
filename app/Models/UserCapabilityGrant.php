<?php

namespace App\Models;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Exceptions\ScopeMismatchException;
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
        'capability' => UserCapability::class,
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function (self $grant) {
            if ($grant->capability && $grant->capability->scope() !== CapabilityScope::DEPARTMENT) {
                throw new ScopeMismatchException('Only DEPARTMENT capabilities can be assigned to user_capability_grants.');
            }
        });
    }

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
