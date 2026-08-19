<?php

namespace App\Services\Workflow;

use App\Exceptions\Workflow\InvalidApprovalWorkflowConfiguration;
use App\Models\ApprovalWorkflow;

class ApprovalWorkflowResolver
{
    /**
     * Resolves the default active published workflow for a new Kaizen instance.
     *
     * @throws InvalidApprovalWorkflowConfiguration
     */
    public function resolveDefaultForNewInstance(): ApprovalWorkflow
    {
        $workflows = ApprovalWorkflow::where('is_active', true)
            ->where('is_default', true)
            ->whereNotNull('published_at')
            ->get();

        if ($workflows->isEmpty()) {
            throw InvalidApprovalWorkflowConfiguration::noDefault();
        }

        if ($workflows->count() > 1) {
            throw InvalidApprovalWorkflowConfiguration::multipleDefaults();
        }

        $workflow = $workflows->first();

        $this->validateWorkflowStructure($workflow);

        return $workflow;
    }

    /**
     * Validates that the workflow has a valid stage structure.
     *
     * @throws InvalidApprovalWorkflowConfiguration
     */
    protected function validateWorkflowStructure(ApprovalWorkflow $workflow): void
    {
        $stages = $workflow->stages()->where('is_active', true)->orderBy('sequence')->get();

        if ($stages->isEmpty()) {
            throw InvalidApprovalWorkflowConfiguration::noActiveStages();
        }

        $finalStages = $stages->where('is_final', true);

        if ($finalStages->count() !== 1) {
            throw InvalidApprovalWorkflowConfiguration::invalidFinalStage();
        }

        $lastStage = $stages->last();
        if (! $lastStage->is_final) {
            throw InvalidApprovalWorkflowConfiguration::invalidFinalStage();
        }
    }
}
