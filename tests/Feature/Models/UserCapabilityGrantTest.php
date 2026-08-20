<?php

namespace Tests\Feature\Models;

use App\Enums\UserCapability;
use App\Models\Department;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCapabilityGrantTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_constraint_on_grants()
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $capability = UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN;

        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => $capability,
        ]);

        $this->expectException(QueryException::class);

        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => $capability,
        ]);
    }
}
