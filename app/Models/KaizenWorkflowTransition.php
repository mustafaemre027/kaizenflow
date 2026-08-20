<?php

namespace App\Models;

use App\Enums\WorkflowAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaizenWorkflowTransition extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Append-only

    protected $fillable = [
        'kaizen_workflow_instance_id',
        'kaizen_id',
        'from_stage_id',
        'to_stage_id',
        'actor_user_id',
        'action',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'action' => WorkflowAction::class,
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(KaizenWorkflowInstance::class, 'kaizen_workflow_instance_id');
    }

    public function kaizen(): BelongsTo
    {
        return $this->belongsTo(Kaizen::class);
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(ApprovalStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(ApprovalStage::class, 'to_stage_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected static function booted()
    {
        static::updating(function ($transition) {
            return false; // Prevent update
        });

        static::deleting(function ($transition) {
            return false; // Prevent delete
        });
    }
}
