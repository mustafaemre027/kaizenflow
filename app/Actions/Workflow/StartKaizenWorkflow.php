<?php

namespace App\Actions\Workflow;

use App\Enums\WorkflowAction;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Services\Workflow\ApprovalWorkflowNavigator;
use App\Services\Workflow\ApprovalWorkflowResolver;
use Exception;
use Illuminate\Support\Facades\DB;

class StartKaizenWorkflow
{
    public function __construct(
        private readonly ApprovalWorkflowResolver $resolver,
        private readonly ApprovalWorkflowNavigator $navigator
    ) {}

    public function execute(Kaizen $kaizen, User $actor): KaizenWorkflowInstance
    {
        if ($kaizen->workflowInstance()->exists()) {
            throw new Exception('Kaizen already has an active workflow instance.');
        }

        $workflow = $this->resolver->resolveDefaultForNewInstance();
        $firstStage = $this->navigator->firstStage($workflow);

        return DB::transaction(function () use ($kaizen, $workflow, $firstStage, $actor) {
            $instance = KaizenWorkflowInstance::create([
                'kaizen_id' => $kaizen->id,
                'approval_workflow_id' => $workflow->id,
                'current_stage_id' => $firstStage->id,
                'started_at' => now(),
            ]);

            $instance->transitions()->create([
                'kaizen_id' => $kaizen->id,
                'from_stage_id' => null,
                'to_stage_id' => $firstStage->id,
                'actor_user_id' => $actor->id,
                'action' => WorkflowAction::START,
                'comment' => 'İş akışı başlatıldı.',
            ]);

            return $instance;
        });
    }
}
