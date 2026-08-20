<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalWorkflowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word().'_WORKFLOW',
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'version' => 1,
            'is_active' => true,
            'is_default' => false,
            'published_at' => now(),
        ];
    }
}
