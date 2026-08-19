<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KaizenWorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'kaizen_id',
        'approval_workflow_id',
        'current_stage_id',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'started_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
        'cancelled_at' => 'immutable_datetime',
    ];

    public function kaizen(): BelongsTo
    {
        return $this->belongsTo(Kaizen::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(ApprovalStage::class, 'current_stage_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(KaizenWorkflowTransition::class);
    }
}
