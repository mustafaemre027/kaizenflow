<?php

namespace Tests\Feature\Models;

use App\Enums\UserCapability;
use App\Exceptions\ScopeMismatchException;
use App\Models\Department;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_model_rejects_system_capability(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $this->expectException(ScopeMismatchException::class);

        UserCapabilityGrant::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => 'authorization.manage',
        ]);
    }

    public function test_factory_cannot_bypass_scope(): void
    {
        $this->expectException(ScopeMismatchException::class);

        UserCapabilityGrant::factory()->create([
            'capability' => 'authorization.manage',
        ]);
    }

    public function test_db_raw_insert_rejects_system_capability(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('user_capability_grants')->insert([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => 'authorization.manage',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
