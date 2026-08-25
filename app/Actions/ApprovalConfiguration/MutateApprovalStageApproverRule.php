<?php

namespace App\Actions\ApprovalConfiguration;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\ApproverResolutionMode;
use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Exceptions\DomainException;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageApproverRule;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Services\AppendAuditLog;
use App\Services\UserCapabilityResolver;
use Illuminate\Support\Facades\DB;

class MutateApprovalStageApproverRule
{
    use HasApprovalConfigurationMutation;

    public function __construct(
        private readonly UserCapabilityResolver $resolver,
        private readonly AppendAuditLog $appendAuditLog
    ) {}

    public function execute(
        User $actor,
        ApprovalStage $stage,
        UserCapability $capability,
        ApprovalApproverScopeSource $scopeSource,
        bool $isActive
    ): void {
        DB::transaction(function () use ($actor, $stage, $capability, $scopeSource, $isActive) {
            // Lock ordering: User -> Grant -> ApprovalWorkflow -> ApprovalStage -> ApprovalStageApproverRule
            $this->authorizeAndLock($actor, $this->resolver);

            $workflow = ApprovalWorkflow::where('id', $stage->approval_workflow_id)
                ->lockForUpdate()
                ->first();

            if (! $workflow) {
                throw new DomainException('Workflow not found.');
            }

            if ($workflow->published_at !== null) {
                throw new DomainException('Cannot mutate rules for a published workflow.');
            }

            if ($workflow->approver_resolution_mode !== ApproverResolutionMode::CAPABILITY_RULE) {
                throw new DomainException('Cannot set capability rules on a LEGACY_GROUP workflow.');
            }

            $lockedStage = ApprovalStage::where('id', $stage->id)
                ->where('approval_workflow_id', $workflow->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedStage) {
                throw new DomainException('Stage not found in workflow.');
            }

            // Validate capability and scope match
            if (
                ($capability->scope() === CapabilityScope::SYSTEM && $scopeSource !== ApprovalApproverScopeSource::SYSTEM) ||
                ($capability->scope() === CapabilityScope::DEPARTMENT && $scopeSource !== ApprovalApproverScopeSource::KAIZEN_DEPARTMENT)
            ) {
                throw new DomainException('Capability and scope mismatch.');
            }

            $rule = ApprovalStageApproverRule::where('approval_stage_id', $lockedStage->id)
                ->lockForUpdate()
                ->first();

            if ($rule) {
                // No-op check
                if ($rule->capability === $capability && $rule->scope_source === $scopeSource && $rule->is_active === $isActive) {
                    return;
                }

                $oldState = $rule->toArray();

                $rule->capability = $capability;
                $rule->scope_source = $scopeSource;
                $rule->is_active = $isActive;
                $rule->save();

                $newState = $rule->toArray();

                $this->appendAuditLog->execute($actor, $rule, 'approval_configuration.approver_rule_updated', [
                    'old_state' => $oldState,
                    'new_state' => $newState,
                ]);
            } else {
                $rule = ApprovalStageApproverRule::create([
                    'approval_stage_id' => $lockedStage->id,
                    'capability' => $capability,
                    'scope_source' => $scopeSource,
                    'is_active' => $isActive,
                ]);

                $this->appendAuditLog->execute($actor, $rule, 'approval_configuration.approver_rule_updated', [
                    'old_state' => null,
                    'new_state' => $rule->toArray(),
                ]);
            }
        });
    }
}
