<?php

namespace Database\Factories;

use App\Models\ApprovalGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalGroupMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'approval_group_id' => ApprovalGroup::factory(),
            'user_id' => User::factory(),
            'is_active' => true,
        ];
    }
}
