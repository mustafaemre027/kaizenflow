<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_workflow_id',
        'code',
        'name',
        'description',
        'sequence',
        'is_final',
        'is_active',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'is_final' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function stageAssignments(): HasMany
    {
        return $this->hasMany(ApprovalStageAssignment::class, 'approval_stage_id');
    }
}
