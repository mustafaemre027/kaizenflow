<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word().'_GROUP',
            'name' => $this->faker->sentence(2),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
