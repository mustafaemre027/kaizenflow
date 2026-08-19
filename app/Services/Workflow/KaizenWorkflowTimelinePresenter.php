<?php

namespace App\Services\Workflow;

use App\Enums\KaizenStatus;
use App\Enums\WorkflowAction;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowTransition;

class KaizenWorkflowTimelinePresenter
{
    public function present(Kaizen $kaizen): object
    {
        $instance = $kaizen->workflowInstance;

        if (! $instance) {
            return (object) [
                'isAvailable' => false,
                'isDraft' => $kaizen->status === KaizenStatus::DRAFT,
                'workflowName' => null,
                'stages' => collect(),
                'history' => collect(),
            ];
        }

        $stages = $instance->workflow->stages->sortBy('sequence')->values();
        $transitions = $instance->transitions()->with(['actor', 'fromStage', 'toStage'])->orderBy('id', 'desc')->get();

        $currentStageId = $instance->current_stage_id;
        $isCompleted = $instance->completed_at !== null && $kaizen->status === KaizenStatus::APPROVED;
        $isRejected = $instance->cancelled_at !== null && $kaizen->status === KaizenStatus::REJECTED;
        $isRevision = $kaizen->status === KaizenStatus::REVISION_REQUESTED;

        $reachedCurrent = false;

        $presentedStages = $stages->map(function ($stage) use (&$reachedCurrent, $currentStageId, $isCompleted, $isRejected, $isRevision) {
            if ($isCompleted) {
                $state = 'completed';
            } elseif ($isRejected) {
                if ($stage->id === $currentStageId) {
                    $state = 'rejected';
                    $reachedCurrent = true;
                } else {
                    $state = $reachedCurrent ? 'upcoming' : 'completed';
                }
            } else {
                if ($stage->id === $currentStageId) {
                    $state = $isRevision ? 'revision' : 'current';
                    $reachedCurrent = true;
                } else {
                    $state = $reachedCurrent ? 'upcoming' : 'completed';
                }
            }

            return (object) [
                'id' => $stage->id,
                'code' => $stage->code,
                'name' => $stage->name,
                'sequence' => $stage->sequence,
                'is_final' => $stage->is_final,
                'presentation_state' => $state,
            ];
        });

        $presentedHistory = $transitions->map(function (KaizenWorkflowTransition $transition) {
            return (object) [
                'actionLabel' => $this->getActionLabel($transition->action),
                'stageContext' => $this->getStageContext($transition),
                'actorName' => $transition->actor ? $transition->actor->name : 'Sistem',
                'timestamp' => $transition->created_at,
                'comment' => $transition->comment,
            ];
        });

        return (object) [
            'isAvailable' => true,
            'isDraft' => false,
            'workflowName' => $instance->workflow->name,
            'stages' => $presentedStages,
            'history' => $presentedHistory,
        ];
    }

    private function getActionLabel(WorkflowAction $action): string
    {
        return match ($action) {
            WorkflowAction::START => 'Süreç Başlatıldı',
            WorkflowAction::APPROVE => 'Onaylandı',
            WorkflowAction::REJECT => 'Reddedildi',
            WorkflowAction::REQUEST_REVISION => 'Revizyon İstendi',
            WorkflowAction::RESUBMIT => 'Yeniden Gönderildi',
        };
    }

    private function getStageContext(KaizenWorkflowTransition $transition): string
    {
        $action = $transition->action;

        if ($action === WorkflowAction::START) {
            return $transition->toStage ? "{$transition->toStage->name} aşamasına gönderildi." : 'Süreç başlatıldı.';
        }

        if ($action === WorkflowAction::REQUEST_REVISION) {
            return $transition->fromStage ? "{$transition->fromStage->name} aşamasında revizyon istendi." : 'Revizyon istendi.';
        }

        if ($action === WorkflowAction::REJECT) {
            return $transition->fromStage ? "{$transition->fromStage->name} aşamasında reddedildi." : 'Reddedildi.';
        }

        if ($action === WorkflowAction::APPROVE) {
            if ($transition->toStage) {
                return $transition->fromStage
                    ? "{$transition->fromStage->name} aşaması onaylandı, {$transition->toStage->name} aşamasına geçildi."
                    : "{$transition->toStage->name} aşamasına geçildi.";
            }

            return $transition->fromStage ? "{$transition->fromStage->name} aşaması tamamlandı." : 'Aşama tamamlandı.';
        }

        if ($action === WorkflowAction::RESUBMIT) {
            return $transition->toStage ? "{$transition->toStage->name} aşaması için yeniden gönderildi." : 'Yeniden gönderildi.';
        }

        return '';
    }
}
