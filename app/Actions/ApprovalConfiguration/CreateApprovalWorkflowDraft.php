<?php

namespace App\Actions\ApprovalConfiguration;

use App\Enums\ApproverResolutionMode;
use App\Exceptions\DomainException;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Services\AppendAuditLog;
use App\Services\UserCapabilityResolver;
use Illuminate\Support\Facades\DB;

class CreateApprovalWorkflowDraft
{
    use HasApprovalConfigurationMutation;

    public function __construct(
        private UserCapabilityResolver $resolver,
        private AppendAuditLog $audit
    ) {}

    public function execute(User $actor, string $code, string $name, ?string $description, array $stages): ApprovalWorkflow
    {
        return DB::transaction(function () use ($actor, $code, $name, $description, $stages) {
            $this->authorizeAndLock($actor, $this->resolver);

            // Lock existing workflows with this code
            $existingWorkflows = ApprovalWorkflow::where('code', $code)->orderBy('id', 'asc')->lockForUpdate()->get();
            $maxVersion = $existingWorkflows->max('version') ?? 0;
            $newVersion = $maxVersion + 1;

            $workflow = ApprovalWorkflow::create([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'version' => $newVersion,
                'is_active' => false,
                'is_default' => false,
                'published_at' => null,
                'approver_resolution_mode' => ApproverResolutionMode::CAPABILITY_RULE,
            ]);

            $this->validateAndCreateStages($workflow, $stages);

            $this->audit->execute($actor, $workflow, 'approval_configuration.created', [
                'actor_user_id' => $actor->id,
                'approval_workflow_id' => $workflow->id,
                'workflow_code' => $workflow->code,
                'workflow_version' => $workflow->version,
                'old_is_active' => false,
                'new_is_active' => false,
                'old_is_default' => false,
                'new_is_default' => false,
                'old_published_at' => null,
                'new_published_at' => null,
            ]);

            return $workflow;
        });
    }

    private function validateAndCreateStages(ApprovalWorkflow $workflow, array $stages): void
    {
        $stageCodes = [];
        $stageSequences = [];
        $finalCount = 0;

        foreach ($stages as $stageData) {
            if (in_array($stageData['code'], $stageCodes)) {
                throw new DomainException("Duplicate stage code: {$stageData['code']}");
            }
            if (in_array($stageData['sequence'], $stageSequences)) {
                throw new DomainException("Duplicate stage sequence: {$stageData['sequence']}");
            }
            if ($stageData['is_final'] ?? false) {
                $finalCount++;
            }

            $stageCodes[] = $stageData['code'];
            $stageSequences[] = $stageData['sequence'];

            ApprovalStage::create([
                'approval_workflow_id' => $workflow->id,
                'code' => $stageData['code'],
                'name' => $stageData['name'],
                'description' => $stageData['description'] ?? null,
                'sequence' => $stageData['sequence'],
                'is_final' => $stageData['is_final'] ?? false,
                'is_active' => true,
            ]);
        }

        if ($finalCount > 1) {
            throw new DomainException('Only one final stage is allowed.');
        }
    }
}
