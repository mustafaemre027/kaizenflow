<?php

namespace Database\Factories;

use App\Enums\UserCapability;
use App\Models\Department;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserCapabilityGrant>
 */
class UserCapabilityGrantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
            'is_active' => true,
        ];
    }
}
