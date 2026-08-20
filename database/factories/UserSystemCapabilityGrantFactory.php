<?php

namespace Database\Factories;

use App\Models\UserSystemCapabilityGrant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSystemCapabilityGrant>
 */
use App\Enums\UserCapability;
use App\Models\User;

class UserSystemCapabilityGrantFactory extends Factory
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
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ];
    }
}
