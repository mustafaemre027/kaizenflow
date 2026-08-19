<?php

namespace App\Actions\Workflow;

use App\Enums\KaizenStatus;
use App\Enums\WorkflowAction;
use App\Exceptions\Workflow\InvalidApprovalWorkflowConfiguration;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\Workflow\ApprovalWorkflowNavigator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgressKaizenWorkflow
{
    public function __construct(private readonly ApprovalWorkflowNavigator $navigator) {}

    public function execute(Kaizen $kaizen, User $actor, WorkflowAction $action, ?string $comment = null): Kaizen
    {
        return DB::transaction(function () use ($kaizen, $actor, $action, $comment) {
            $lockedKaizen = Kaizen::where('id', $kaizen->id)->lockForUpdate()->firstOrFail();
            $instance = $lockedKaizen->workflowInstance()->lockForUpdate()->first();

            if (! $instance) {
                throw new \DomainException('No active workflow instance found.');
            }

            if ($instance->completed_at || $instance->cancelled_at) {
                throw new \DomainException('Workflow instance is already closed.');
            }

            if ($lockedKaizen->status !== KaizenStatus::SUBMITTED) {
                throw new \DomainException('Kaizen must be in SUBMITTED state to progress workflow.');
            }

            $currentStage = $instance->currentStage;

            if (! $currentStage || $currentStage->approval_workflow_id !== $instance->approval_workflow_id) {
                throw new \DomainException('Workflow current stage is corrupted.');
            }

            $comment = is_string($comment) ? trim($comment) : null;
            $comment = $comment === '' ? null : $comment;

            if ($action === WorkflowAction::REQUEST_REVISION || $action === WorkflowAction::REJECT) {
                if (! $comment) {
                    throw ValidationException::withMessages([
                        'comment' => 'Bu işlem için açıklama zorunludur.',
                    ]);
                }
                if (mb_strlen($comment) > 2000) {
                    throw ValidationException::withMessages([
                        'comment' => 'Açıklama 2000 karakterden uzun olamaz.',
                    ]);
                }
            }

            if ($action === WorkflowAction::APPROVE) {
                if ($currentStage->is_final) {
                    $nextStage = null;
                } else {
                    $nextStage = $this->navigator->nextStage($currentStage);
                    if (! $nextStage) {
                        throw new InvalidApprovalWorkflowConfiguration('Workflow has no next stage but current stage is not final.');
                    }
                }

                $instance->transitions()->create([
                    'kaizen_id' => $lockedKaizen->id,
                    'from_stage_id' => $currentStage->id,
                    'to_stage_id' => $nextStage ? $nextStage->id : null,
                    'actor_user_id' => $actor->id,
                    'action' => WorkflowAction::APPROVE,
                    'comment' => $comment,
                ]);

                if ($nextStage) {
                    $instance->current_stage_id = $nextStage->id;
                    $instance->save();
                    // Kaizen lifecycle remains SUBMITTED
                } else {
                    $instance->completed_at = now();
                    $instance->save();

                    $lockedKaizen->status = KaizenStatus::APPROVED;
                    $lockedKaizen->save();

                    $lockedKaizen->statusHistories()->create([
                        'actor_user_id' => $actor->id,
                        'transition_code' => 'APPROVE',
                        'from_status' => KaizenStatus::SUBMITTED->value,
                        'to_status' => KaizenStatus::APPROVED->value,
                        'reason' => $comment,
                        'metadata' => null,
                    ]);
                }
            } elseif ($action === WorkflowAction::REQUEST_REVISION) {
                $instance->transitions()->create([
                    'kaizen_id' => $lockedKaizen->id,
                    'from_stage_id' => $currentStage->id,
                    'to_stage_id' => $currentStage->id,
                    'actor_user_id' => $actor->id,
                    'action' => WorkflowAction::REQUEST_REVISION,
                    'comment' => $comment,
                ]);

                $lockedKaizen->status = KaizenStatus::REVISION_REQUESTED;
                $lockedKaizen->save();

                $lockedKaizen->statusHistories()->create([
                    'actor_user_id' => $actor->id,
                    'transition_code' => 'REQUEST_REVISION',
                    'from_status' => KaizenStatus::SUBMITTED->value,
                    'to_status' => KaizenStatus::REVISION_REQUESTED->value,
                    'reason' => $comment,
                    'metadata' => null,
                ]);
            } elseif ($action === WorkflowAction::REJECT) {
                $instance->transitions()->create([
                    'kaizen_id' => $lockedKaizen->id,
                    'from_stage_id' => $currentStage->id,
                    'to_stage_id' => $currentStage->id,
                    'actor_user_id' => $actor->id,
                    'action' => WorkflowAction::REJECT,
                    'comment' => $comment,
                ]);

                $instance->cancelled_at = now();
                $instance->save();

                $lockedKaizen->status = KaizenStatus::REJECTED;
                $lockedKaizen->save();

                $lockedKaizen->statusHistories()->create([
                    'actor_user_id' => $actor->id,
                    'transition_code' => 'REJECT',
                    'from_status' => KaizenStatus::SUBMITTED->value,
                    'to_status' => KaizenStatus::REJECTED->value,
                    'reason' => $comment,
                    'metadata' => null,
                ]);
            } else {
                throw new \InvalidArgumentException('Unsupported workflow action.');
            }

            return $lockedKaizen->refresh();
        });
    }
}
