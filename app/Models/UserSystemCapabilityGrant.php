<?php

namespace App\Models;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Exceptions\ScopeMismatchException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSystemCapabilityGrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'capability',
        'granted_by_user_id',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'capability' => UserCapability::class,
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function (self $grant) {
            if ($grant->capability && $grant->capability->scope() !== CapabilityScope::SYSTEM) {
                throw new ScopeMismatchException('Only SYSTEM capabilities can be assigned to user_system_capability_grants.');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
