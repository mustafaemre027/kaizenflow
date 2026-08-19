<?php

namespace Database\Factories;

use App\Models\ApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalStageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'approval_workflow_id' => ApprovalWorkflow::factory(),
            'code' => $this->faker->unique()->word().'_STAGE',
            'name' => $this->faker->sentence(2),
            'description' => $this->faker->sentence(),
            'sequence' => $this->faker->unique()->numberBetween(1, 100),
            'is_final' => false,
            'is_active' => true,
        ];
    }
}
