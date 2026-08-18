<?php

namespace App\Models;

use App\Enums\KaizenPriority;
use App\Enums\KaizenStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kaizen extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'category_id',
        'title',
        'current_situation',
        'proposed_situation',
        'expected_benefit',
        'priority',
        'target_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => KaizenStatus::class,
            'priority' => KaizenPriority::class,
            'target_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function scopeForCreator(Builder $query, User $user): void
    {
        $query->where('creator_user_id', $user->id);
    }

    public function scopeForDepartment(Builder $query, Department $department): void
    {
        $query->where('department_id', $department->id);
    }

    public function scopeAssignedTo(Builder $query, User $user): void
    {
        $query->where('assigned_user_id', $user->id);
    }

    public function scopeWithStatus(Builder $query, KaizenStatus $status): void
    {
        $query->where('status', $status->value);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(KaizenStatusHistory::class);
    }
}
