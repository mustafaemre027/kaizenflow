<?php

namespace App\Actions\ApprovalConfiguration;

use App\Exceptions\DomainException;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Services\AppendAuditLog;
use App\Services\UserCapabilityResolver;
use Illuminate\Support\Facades\DB;

class UpdateApprovalWorkflowDraft
{
    use HasApprovalConfigurationMutation;

    public function __construct(
        private UserCapabilityResolver $resolver,
        private AppendAuditLog $audit
    ) {}

    public function execute(User $actor, ApprovalWorkflow $workflow, string $name, ?string $description, array $stages): ApprovalWorkflow
    {
        return DB::transaction(function () use ($actor, $workflow, $name, $description, $stages) {
            $this->authorizeAndLock($actor, $this->resolver);

            $workflow = ApprovalWorkflow::where('id', $workflow->id)->lockForUpdate()->firstOrFail();

            if ($workflow->published_at !== null) {
                throw new DomainException('Cannot update a published workflow.');
            }

            $hasChanges = false;
            if ($workflow->name !== $name || $workflow->description !== $description) {
                $hasChanges = true;
                $workflow->update([
                    'name' => $name,
                    'description' => $description,
                ]);
            }

            $hasStageChanges = $this->updateStages($workflow, $stages);

            if ($hasChanges || $hasStageChanges) {
                $this->audit->execute($actor, $workflow, 'approval_configuration.updated', [
                    'actor_user_id' => $actor->id,
                    'approval_workflow_id' => $workflow->id,
                    'workflow_code' => $workflow->code,
                    'workflow_version' => $workflow->version,
                    'old_is_active' => $workflow->is_active,
                    'new_is_active' => $workflow->is_active,
                    'old_is_default' => $workflow->is_default,
                    'new_is_default' => $workflow->is_default,
                    'old_published_at' => $workflow->published_at?->toIso8601String(),
                    'new_published_at' => $workflow->published_at?->toIso8601String(),
                ]);
            }

            return $workflow;
        });
    }

    private function updateStages(ApprovalWorkflow $workflow, array $stages): bool
    {
        $existingStages = ApprovalStage::where('approval_workflow_id', $workflow->id)->orderBy('id', 'asc')->lockForUpdate()->get();

        $stageCodes = [];
        $stageSequences = [];
        $finalCount = 0;

        $newIds = collect($stages)->pluck('id')->filter()->toArray();

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
        }

        if ($finalCount > 1) {
            throw new DomainException('Only one final stage is allowed.');
        }

        $hasChanges = false;
        $stageUpdates = [];
        $stagesToDeactivate = [];
        $stagesToCreate = [];

        foreach ($existingStages as $existingStage) {
            if (! in_array($existingStage->id, $newIds)) {
                if ($existingStage->is_active) {
                    $stagesToDeactivate[] = $existingStage;
                    $hasChanges = true;
                }
            }
        }

        foreach ($stages as $stageData) {
            if (isset($stageData['id'])) {
                $stage = $existingStages->where('id', $stageData['id'])->first();
                if ($stage) {
                    $changes = [];
                    if ($stage->code !== $stageData['code']) {
                        $changes['code'] = $stageData['code'];
                    }
                    if ($stage->name !== $stageData['name']) {
                        $changes['name'] = $stageData['name'];
                    }
                    if ($stage->description !== ($stageData['description'] ?? null)) {
                        $changes['description'] = $stageData['description'] ?? null;
                    }
                    if ($stage->sequence !== $stageData['sequence']) {
                        $changes['sequence'] = $stageData['sequence'];
                    }
                    if ($stage->is_final !== ($stageData['is_final'] ?? false)) {
                        $changes['is_final'] = $stageData['is_final'] ?? false;
                    }
                    if (! $stage->is_active) {
                        $changes['is_active'] = true;
                    }

                    if (! empty($changes)) {
                        $stageUpdates[$stage->id] = $changes;
                        $hasChanges = true;
                    }
                }
            } else {
                $stagesToCreate[] = $stageData;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            return false;
        }

        // Shift all existing stages to a temporary sequence to avoid unique constraint collisions
        $tempOffset = 100000;
        foreach ($existingStages as $stage) {
            $stage->sequence = $tempOffset + $stage->id;
            $stage->save();
        }

        // Apply real updates deterministically
        foreach ($stagesToDeactivate as $stage) {
            // Leave sequence as tempOffset + id to avoid collisions with active stages
            $stage->is_active = false;
            $stage->save();
        }

        foreach ($existingStages as $stage) {
            if (in_array($stage->id, $newIds)) {
                $finalSeq = collect($stages)->firstWhere('id', $stage->id)['sequence'];
                $updates = $stageUpdates[$stage->id] ?? [];
                $updates['sequence'] = $finalSeq;
                $stage->forceFill($updates);
                $stage->save();
            }
        }

        foreach ($stagesToCreate as $stageData) {
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

        return true;
    }
}
