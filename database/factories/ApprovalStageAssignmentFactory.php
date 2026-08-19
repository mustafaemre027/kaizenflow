<?php

namespace Database\Factories;

use App\Models\ApprovalGroup;
use App\Models\ApprovalStage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalStageAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'approval_stage_id' => ApprovalStage::factory(),
            'approval_group_id' => ApprovalGroup::factory(),
            'scope' => 'GLOBAL',
            'department_id' => null,
            'is_active' => true,
        ];
    }
}
