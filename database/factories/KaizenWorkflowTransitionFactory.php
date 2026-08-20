<?php

namespace Database\Factories;

use App\Enums\WorkflowAction;
use App\Models\ApprovalStage;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KaizenWorkflowTransitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kaizen_workflow_instance_id' => KaizenWorkflowInstance::factory(),
            'kaizen_id' => Kaizen::factory(),
            'from_stage_id' => ApprovalStage::factory(),
            'to_stage_id' => ApprovalStage::factory(),
            'actor_user_id' => User::factory(),
            'action' => WorkflowAction::APPROVE,
            'comment' => $this->faker->sentence(),
            'metadata' => null,
        ];
    }
}
