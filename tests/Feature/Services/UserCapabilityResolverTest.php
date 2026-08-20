<?php

namespace Tests\Feature\Services;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Exceptions\ScopeMismatchException;
use App\Models\Department;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use App\Models\UserSystemCapabilityGrant;
use App\Services\UserCapabilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCapabilityResolverTest extends TestCase
{
    use RefreshDatabase;

    private UserCapabilityResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(UserCapabilityResolver::class);
    }

    public function test_allows_system_grants_access_for_active_user_and_active_grant(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => 'authorization.manage',
            'is_active' => true,
        ]);

        $this->assertTrue($this->resolver->allowsSystem($user, UserCapability::from('authorization.manage')));
    }

    public function test_allows_system_denies_access_for_inactive_grant(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => 'authorization.manage',
            'is_active' => false,
        ]);

        $this->assertFalse($this->resolver->allowsSystem($user, UserCapability::from('authorization.manage')));
    }

    public function test_allows_system_denies_access_for_inactive_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => 'authorization.manage',
            'is_active' => true,
        ]);

        $this->assertFalse($this->resolver->allowsSystem($user, UserCapability::from('authorization.manage')));
    }

    public function test_allows_system_denies_access_when_grant_belongs_to_another_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $anotherUser = User::factory()->create(['is_active' => true]);
        
        UserSystemCapabilityGrant::create([
            'user_id' => $anotherUser->id,
            'capability' => 'authorization.manage',
            'is_active' => true,
        ]);

        $this->assertFalse($this->resolver->allowsSystem($user, UserCapability::from('authorization.manage')));
    }

    public function test_allows_system_denies_access_despite_admin_role(): void
    {
        $adminUser = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::ADMIN,
        ]);
        
        $this->assertFalse($this->resolver->allowsSystem($adminUser, UserCapability::from('authorization.manage')));
    }

    public function test_allows_system_throws_exception_for_department_capability(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        
        $this->expectException(ScopeMismatchException::class);
        $this->resolver->allowsSystem($user, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);
    }

    public function test_allows_throws_exception_for_system_capability(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $department = Department::factory()->create();
        
        $this->expectException(ScopeMismatchException::class);
        $this->resolver->allows($user, UserCapability::from('authorization.manage'), $department->id);
    }

    public function test_department_grant_cannot_provide_system_access(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $department = Department::factory()->create();

        // Let's directly create via DB so it bypasses our model's exception for this specific edge case test.
        \Illuminate\Support\Facades\DB::table('user_capability_grants')->insert([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => 'authorization.manage',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse($this->resolver->allowsSystem($user, UserCapability::from('authorization.manage')));
    }

    public function test_system_grant_cannot_provide_department_access(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $department = Department::factory()->create();

        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => 'authorization.manage',
            'is_active' => true,
        ]);

        // Trying to use allows with system capability will anyway throw ScopeMismatchException
        $this->expectException(ScopeMismatchException::class);
        $this->resolver->allows($user, UserCapability::from('authorization.manage'), $department->id);
    }

    public function test_allows_maintains_legacy_department_behavior(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $department = Department::factory()->create();

        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
            'is_active' => true,
        ]);

        $this->assertTrue($this->resolver->allows($user, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN, $department->id));
        $this->assertFalse($this->resolver->allows($user, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN, 999));
        $this->assertFalse($this->resolver->allows($user, UserCapability::KAIZEN_IMPLEMENTATION_START, $department->id));
    }
}
