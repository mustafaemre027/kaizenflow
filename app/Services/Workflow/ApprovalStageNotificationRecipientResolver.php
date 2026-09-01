<?php

namespace App\Services\Workflow;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\ApproverResolutionMode;
use App\Enums\CapabilityScope;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Support\Collection;

class ApprovalStageNotificationRecipientResolver
{
    /**
     * Resolves active, ready, verified eligible users for the current stage.
     * Excludes the Kaizen creator.
     *
     * @return Collection<int, User>
     */
    public function resolveCurrentStage(Kaizen $kaizen): Collection
    {
        $instance = $kaizen->workflowInstance;

        if (! $instance || $instance->completed_at || $instance->cancelled_at) {
            return collect();
        }

        $currentStage = $instance->currentStage;
        if (! $currentStage || ! $currentStage->is_active) {
            return collect();
        }

        $workflow = $instance->workflow;
        if (! $workflow) {
            return collect();
        }

        $creatorId = $kaizen->creator_user_id;

        $query = User::query()
            ->where('is_active', true)
            ->where('must_set_password', false)
            ->whereNotNull('email_verified_at')
            ->where('id', '!=', $creatorId);

        if ($workflow->approver_resolution_mode === ApproverResolutionMode::CAPABILITY_RULE) {
            $rule = $currentStage->approverRule;
            if (! $rule || ! $rule->is_active) {
                return collect();
            }

            $capability = $rule->capability;

            if ($capability->scope() === CapabilityScope::SYSTEM && $rule->scope_source === ApprovalApproverScopeSource::SYSTEM) {
                $query->whereHas('systemCapabilityGrants', function ($q) use ($capability) {
                    $q->where('is_active', true)
                        ->where('capability', $capability->value);
                });
            } elseif ($capability->scope() === CapabilityScope::DEPARTMENT && $rule->scope_source === ApprovalApproverScopeSource::KAIZEN_DEPARTMENT) {
                $query->whereHas('capabilityGrants', function ($q) use ($capability, $kaizen) {
                    $q->where('is_active', true)
                        ->where('department_id', $kaizen->department_id)
                        ->where('capability', $capability->value);
                });
            } else {
                return collect();
            }

        } elseif ($workflow->approver_resolution_mode === ApproverResolutionMode::LEGACY_GROUP) {
            $query->whereHas('approvalGroupMemberships', function ($q) use ($currentStage, $kaizen) {
                $q->where('is_active', true)
                    ->whereHas('group', function ($gq) use ($currentStage, $kaizen) {
                        $gq->where('is_active', true)
                            ->whereHas('stageAssignments', function ($saq) use ($currentStage, $kaizen) {
                                $saq->where('approval_stage_id', $currentStage->id)
                                    ->where('is_active', true)
                                    ->where(function ($scopeQuery) use ($kaizen) {
                                        $scopeQuery->where('scope', 'GLOBAL')
                                            ->orWhere(function ($deptQuery) use ($kaizen) {
                                                $deptQuery->where('scope', 'DEPARTMENT')
                                                    ->where('department_id', $kaizen->department_id);
                                            });
                                    });
                            });
                    });
            });
        } else {
            return collect();
        }

        return $query->get()->unique('id');
    }
}
