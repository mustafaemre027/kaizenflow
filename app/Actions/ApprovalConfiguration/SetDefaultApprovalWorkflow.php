<?php

namespace App\Actions\ApprovalConfiguration;

use App\Exceptions\DomainException;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Services\AppendAuditLog;
use App\Services\UserCapabilityResolver;
use Illuminate\Support\Facades\DB;

class SetDefaultApprovalWorkflow
{
    use HasApprovalConfigurationMutation;

    public function __construct(
        private UserCapabilityResolver $resolver,
        private AppendAuditLog $audit
    ) {}

    public function execute(User $actor, ApprovalWorkflow $targetWorkflow): ApprovalWorkflow
    {
        return DB::transaction(function () use ($actor, $targetWorkflow) {
            $this->authorizeAndLock($actor, $this->resolver);

            // Lock all workflows order by id
            $allWorkflows = ApprovalWorkflow::orderBy('id', 'asc')->lockForUpdate()->get();
            $workflow = $allWorkflows->where('id', $targetWorkflow->id)->first();

            if (! $workflow || $workflow->published_at === null || ! $workflow->is_active) {
                throw new DomainException('Only published and active workflows can be set as default.');
            }

            if ($workflow->is_default) {
                return $workflow; // No-op
            }

            $oldDefault = $allWorkflows->where('is_default', true)->first();
            if ($oldDefault) {
                $oldDefault->update(['is_default' => false]);
            }

            $workflow->update(['is_default' => true]);

            $this->audit->execute($actor, $workflow, 'approval_configuration.default_set', [
                'actor_user_id' => $actor->id,
                'approval_workflow_id' => $workflow->id,
                'workflow_code' => $workflow->code,
                'workflow_version' => $workflow->version,
                'old_is_active' => $workflow->is_active,
                'new_is_active' => $workflow->is_active,
                'old_is_default' => false,
                'new_is_default' => true,
                'previous_default_workflow_id' => $oldDefault?->id,
            ]);

            return $workflow;
        });
    }
}
