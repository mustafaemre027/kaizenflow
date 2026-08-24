<?php

namespace App\Actions\ApprovalConfiguration;

use App\Exceptions\DomainException;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Services\AppendAuditLog;
use App\Services\UserCapabilityResolver;
use Illuminate\Support\Facades\DB;

class PublishApprovalWorkflow
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

            if ($workflow->published_at !== null) {
                // idempotent no-op or throw? "tek bir davranış seçip testle sabitle" -> I will return early as no-op.
                return $workflow;
            }

            $stages = ApprovalStage::where('approval_workflow_id', $workflow->id)
                ->where('is_active', true)
                ->orderBy('sequence', 'asc')
                ->lockForUpdate()
                ->get();

            if ($stages->isEmpty()) {
                throw new DomainException('Cannot publish workflow without any active stages.');
            }

            $finalCount = 0;
            $lastSequence = -1;
            foreach ($stages as $stage) {
                if ($stage->sequence <= $lastSequence) {
                    throw new DomainException('Stage sequences must be strictly monotonically increasing.');
                }
                $lastSequence = $stage->sequence;
                if ($stage->is_final) {
                    $finalCount++;
                }
            }

            if ($finalCount === 0) {
                throw new DomainException('Cannot publish workflow without a final stage.');
            }
            if ($finalCount > 1) {
                throw new DomainException('Cannot publish workflow with multiple final stages.');
            }
            if (! $stages->last()->is_final) {
                throw new DomainException('The final stage must be the last stage in sequence.');
            }

            $oldIsActive = $workflow->is_active;

            $workflow->update([
                'published_at' => now(),
                'is_active' => true,
            ]);

            $this->audit->execute($actor, $workflow, 'approval_configuration.published', [
                'actor_user_id' => $actor->id,
                'approval_workflow_id' => $workflow->id,
                'workflow_code' => $workflow->code,
                'workflow_version' => $workflow->version,
                'old_is_active' => $oldIsActive,
                'new_is_active' => true,
                'old_is_default' => $workflow->is_default,
                'new_is_default' => $workflow->is_default,
                'old_published_at' => null,
                'new_published_at' => $workflow->published_at->toIso8601String(),
            ]);

            return $workflow;
        });
    }
}
