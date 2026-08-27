<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\WorkflowAction;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function opexReviews()
    {
        return $this->hasMany(Kaizen::class, 'opex_reviewer_id');
    }

    public function capabilityGrants()
    {
        return $this->hasMany(UserCapabilityGrant::class);
    }

    public function createdKaizens(): HasMany
    {
        return $this->hasMany(Kaizen::class, 'creator_user_id');
    }

    public function assignedKaizens(): HasMany
    {
        return $this->hasMany(Kaizen::class, 'assigned_user_id');
    }

    public function kaizenStatusHistories(): HasMany
    {
        return $this->hasMany(KaizenStatusHistory::class, 'actor_user_id');
    }

    public function approvalGroupMemberships(): HasMany
    {
        return $this->hasMany(ApprovalGroupMember::class);
    }

    public function canAccessReviewedHistory(): bool
    {
        $hasActiveMembership = $this->approvalGroupMemberships()
            ->where('is_active', true)
            ->whereHas('group', function ($q) {
                $q->where('is_active', true);
            })->exists();

        if ($hasActiveMembership) {
            return true;
        }

        return KaizenWorkflowTransition::where('actor_user_id', $this->id)
            ->whereIn('action', array_map(fn ($a) => $a->value, WorkflowAction::reviewActions()))
            ->exists();
    }

    public function emailVerificationCode(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmailVerificationCode::class);
    }
}
