<?php

namespace Database\Factories;

use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use Illuminate\Database\Eloquent\Factories\Factory;

class KaizenWorkflowInstanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kaizen_id' => Kaizen::factory(),
            'approval_workflow_id' => ApprovalWorkflow::factory(),
            'current_stage_id' => ApprovalStage::factory(),
            'started_at' => now(),
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }
}
