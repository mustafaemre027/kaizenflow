<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStageAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_stage_id',
        'approval_group_id',
        'scope',
        'department_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ApprovalStage::class, 'approval_stage_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ApprovalGroup::class, 'approval_group_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
