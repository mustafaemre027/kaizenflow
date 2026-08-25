<?php

namespace Database\Factories;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\UserCapability;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageApproverRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalStageApproverRule>
 */
class ApprovalStageApproverRuleFactory extends Factory
{
    protected $model = ApprovalStageApproverRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_stage_id' => ApprovalStage::factory(),
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ];
    }
}
