<?php

namespace App\Actions\ApprovalConfiguration;

use App\Exceptions\DomainException;
use App\Models\ApprovalWorkflow;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Services\AppendAuditLog;
use App\Services\UserCapabilityResolver;
use Illuminate\Support\Facades\DB;

class DeactivateApprovalWorkflow
{
    use HasApprovalConfigurationMutation;

    public function __construct(
        private UserCapabilityResolver $resolver,
        private AppendAuditLog $audit
    ) {}

    public function execute(User $actor, ApprovalWorkflow $workflow): ApprovalWorkflow
    {
        return DB::transaction(function () use ($actor, $workflow) {
            $this->authorizeAndLock($actor, $this->resolver);

            $workflow = ApprovalWorkflow::where('id', $workflow->id)->lockForUpdate()->firstOrFail();

            if (! $workflow->is_active) {
                return $workflow; // No-op
            }

            if ($workflow->is_default) {
                throw new DomainException('Cannot deactivate the default workflow.');
            }

            // Check for non-terminal instances
            // Lock order: 5. kaizen_workflow_instances.id ASC
            $activeInstancesCount = KaizenWorkflowInstance::where('approval_workflow_id', $workflow->id)
                ->whereNull('completed_at')
                ->whereNull('cancelled_at')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->count();

            if ($activeInstancesCount > 0) {
                throw new DomainException('Cannot deactivate workflow with active non-terminal instances.');
            }

            $workflow->update(['is_active' => false]);

            $this->audit->execute($actor, $workflow, 'approval_configuration.deactivated', [
                'actor_user_id' => $actor->id,
                'approval_workflow_id' => $workflow->id,
                'workflow_code' => $workflow->code,
                'workflow_version' => $workflow->version,
                'old_is_active' => true,
                'new_is_active' => false,
                'old_is_default' => false,
                'new_is_default' => false,
            ]);

            return $workflow;
        });
    }
}
