<?php

namespace App\Services\Workflow;

use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;

class ApprovalWorkflowNavigator
{
    /**
     * Gets the first active stage for a given workflow.
     */
    public function firstStage(ApprovalWorkflow $workflow): ?ApprovalStage
    {
        return $workflow->stages()
            ->where('is_active', true)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Gets the next active stage after the current stage.
     */
    public function nextStage(ApprovalStage $currentStage): ?ApprovalStage
    {
        if ($currentStage->is_final) {
            return null;
        }

        return $currentStage->workflow->stages()
            ->where('is_active', true)
            ->where('sequence', '>', $currentStage->sequence)
            ->orderBy('sequence')
            ->first();
    }
}
