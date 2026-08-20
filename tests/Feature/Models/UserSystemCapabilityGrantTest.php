<?php

namespace Tests\Feature\Models;

use App\Enums\UserCapability;
use App\Exceptions\ScopeMismatchException;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserSystemCapabilityGrantTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_system_grant(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $grant = UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => 'authorization.manage',
            'granted_by_user_id' => $admin->id,
        ]);

        $this->assertTrue($grant->is_active);
        $this->assertEquals($user->id, $grant->user_id);
        $this->assertEquals($admin->id, $grant->granted_by_user_id);
        $this->assertEquals(UserCapability::from('authorization.manage'), $grant->capability);
    }

    public function test_same_user_and_capability_cannot_be_created_twice(): void
    {
        $user = User::factory()->create();

        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => 'authorization.manage',
        ]);

        $this->expectException(QueryException::class);

        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => 'authorization.manage',
        ]);
    }

    public function test_model_rejects_department_capability(): void
    {
        $user = User::factory()->create();

        $this->expectException(ScopeMismatchException::class);

        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => 'kaizen.implementation.assign',
        ]);
    }

    public function test_factory_cannot_bypass_scope(): void
    {
        $this->expectException(ScopeMismatchException::class);

        UserSystemCapabilityGrant::factory()->create([
            'capability' => 'kaizen.implementation.assign',
        ]);
    }

    public function test_db_raw_insert_rejects_department_capability(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('user_system_capability_grants')->insert([
            'user_id' => $user->id,
            'capability' => 'kaizen.implementation.assign',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
