<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\Kaizens\StartKaizenImplementation;
use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaizenImplementationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $opex;

    private User $employee;

    private Department $dept1;

    private Department $dept2;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept1 = Department::factory()->create(['name' => 'Dept 1']);
        $this->dept2 = Department::factory()->create(['name' => 'Dept 2']);
        $this->category = Category::factory()->create();

        $this->admin = User::factory()->create(['department_id' => $this->dept1->id]);
        $this->manager = User::factory()->create(['department_id' => $this->dept1->id]);
        $this->opex = User::factory()->create(['department_id' => $this->dept1->id]);
        $this->employee = User::factory()->create(['department_id' => $this->dept1->id]);
    }

    private function grant(User $user, Department $dept, UserCapability $capability): void
    {
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $dept->id,
            'capability' => $capability->value,
            'is_active' => true,
        ]);
    }

    private function createKaizen(string $status = KaizenStatus::APPROVED->value): Kaizen
    {
        return Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'category_id' => $this->category->id,
            'status' => $status,
        ]);
    }

    // AUTHENTICATION

    public function test_guests_are_redirected_to_login(): void
    {
        $kaizen = $this->createKaizen();

        $this->post(route('kaizens.implementation.assign', $kaizen))
            ->assertRedirect(route('login'));

        $this->post(route('kaizens.implementation.start', $kaizen))
            ->assertRedirect(route('login'));

        $this->post(route('kaizens.implementation.complete', $kaizen))
            ->assertRedirect(route('login'));
    }

    public function test_get_method_is_not_allowed(): void
    {
        $this->actingAs($this->admin);
        $kaizen = $this->createKaizen();

        $this->get('/kaizens/'.$kaizen->id.'/implementation/assign')
            ->assertStatus(405);
    }

    // ASSIGN ENDPOINT

    public function test_authorized_user_with_grant_can_assign(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);
        $kaizen = $this->createKaizen();
        $assignee = User::factory()->create();

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.assign', $kaizen), [
                'assigned_user_id' => $assignee->id,
                'target_date' => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertRedirect(route('kaizens.show', $kaizen))
            ->assertSessionHas('success', 'Uygulama sorumlusu ve hedef tarih kaydedildi.');

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'assigned_user_id' => $assignee->id,
            'status' => KaizenStatus::APPROVED->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->opex->id,
            'auditable_type' => Kaizen::class,
            'auditable_id' => $kaizen->id,
            'event' => 'implementation.assigned',
        ]);
    }

    public function test_roles_without_grant_cannot_assign(): void
    {
        $kaizen = $this->createKaizen();
        $assignee = User::factory()->create();
        $payload = ['assigned_user_id' => $assignee->id, 'target_date' => now()->addDays(5)->format('Y-m-d')];

        $this->actingAs($this->opex)->post(route('kaizens.implementation.assign', $kaizen), $payload)->assertForbidden();
        $this->actingAs($this->manager)->post(route('kaizens.implementation.assign', $kaizen), $payload)->assertForbidden();
        $this->actingAs($this->admin)->post(route('kaizens.implementation.assign', $kaizen), $payload)->assertForbidden();
    }

    public function test_grant_from_different_department_cannot_assign(): void
    {
        $this->grant($this->opex, $this->dept2, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);
        $kaizen = $this->createKaizen();
        $payload = ['assigned_user_id' => $this->employee->id, 'target_date' => now()->addDays(5)->format('Y-m-d')];

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.assign', $kaizen), $payload)
            ->assertForbidden();
    }

    public function test_assignee_cannot_assign_just_because_they_are_assigned(): void
    {
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'category_id' => $this->category->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => $this->employee->id,
        ]);

        $payload = ['assigned_user_id' => $this->opex->id, 'target_date' => now()->addDays(5)->format('Y-m-d')];

        $this->actingAs($this->employee)
            ->post(route('kaizens.implementation.assign', $kaizen), $payload)
            ->assertForbidden();
    }

    public function test_assign_validation_errors(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);
        $kaizen = $this->createKaizen();

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.assign', $kaizen), [])
            ->assertSessionHasErrors(['assigned_user_id', 'target_date']);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.assign', $kaizen), [
                'assigned_user_id' => 99999,
                'target_date' => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertSessionHasErrors(['assigned_user_id']);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.assign', $kaizen), [
                'assigned_user_id' => $this->employee->id,
                'target_date' => now()->subDays(1)->format('Y-m-d'),
            ])
            ->assertSessionHasErrors(['target_date']);
    }

    public function test_client_cannot_inject_audit_metadata_in_assign(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);
        $kaizen = $this->createKaizen();
        $assignee = User::factory()->create();

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.assign', $kaizen), [
                'assigned_user_id' => $assignee->id,
                'target_date' => now()->addDays(5)->format('Y-m-d'),
                'actor_id' => 999,
                'status' => KaizenStatus::COMPLETED->value,
            ])
            ->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'status' => KaizenStatus::APPROVED->value,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'actor_user_id' => 999,
        ]);
    }

    // START ENDPOINT

    public function test_authorized_user_can_start_implementation(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_START);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => $this->employee->id,
            'target_date' => now()->addDays(5),
        ]);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.start', $kaizen))
            ->assertRedirect(route('kaizens.show', $kaizen))
            ->assertSessionHas('success', 'Kaizen uygulama süreci başlatıldı.');

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'status' => KaizenStatus::IN_PROGRESS->value,
        ]);

        $this->assertNotNull($kaizen->fresh()->started_at);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'to_status' => KaizenStatus::IN_PROGRESS->value,
        ]);
    }

    public function test_assign_or_complete_grant_cannot_start(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);
        $this->grant($this->manager, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE);

        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => $this->employee->id,
            'target_date' => now()->addDays(5),
        ]);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.start', $kaizen))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->post(route('kaizens.implementation.start', $kaizen))
            ->assertForbidden();
    }

    public function test_cannot_start_unassigned_kaizen(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_START);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => null,
            'target_date' => null,
        ]);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.start', $kaizen))
            ->assertSessionHasErrors(['error']);
    }

    public function test_generic_exception_is_not_masked_by_start_endpoint(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_START);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => $this->employee->id,
            'target_date' => now()->addDays(5),
        ]);

        // Mock the action to throw a generic exception
        $this->mock(StartKaizenImplementation::class, function ($mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Critical DB failure'));
        });

        // The controller should NOT catch this \Exception and redirect with error.
        // It should let it bubble up, resulting in a 500 error.
        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.start', $kaizen))
            ->assertStatus(500);
    }

    // COMPLETE ENDPOINT

    public function test_authorized_user_can_complete_implementation(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::IN_PROGRESS->value,
            'assigned_user_id' => $this->employee->id,
            'target_date' => now()->addDays(5),
            'started_at' => now(),
        ]);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.complete', $kaizen), [
                'actual_result' => 'Implementation finished successfully.',
            ])
            ->assertRedirect(route('kaizens.show', $kaizen))
            ->assertSessionHas('success', 'Kaizen uygulama süreci tamamlandı.');

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'status' => KaizenStatus::COMPLETED->value,
            'actual_result' => 'Implementation finished successfully.',
        ]);

        $this->assertNotNull($kaizen->fresh()->completed_at);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'to_status' => KaizenStatus::COMPLETED->value,
        ]);
    }

    public function test_complete_validation_errors(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::IN_PROGRESS->value,
            'assigned_user_id' => $this->employee->id,
        ]);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.complete', $kaizen), [])
            ->assertSessionHasErrors(['actual_result']);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.complete', $kaizen), [
                'actual_result' => '   ',
            ])
            ->assertSessionHasErrors(['actual_result']);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.complete', $kaizen), [
                'actual_result' => str_repeat('A', 5001),
            ])
            ->assertSessionHasErrors(['actual_result']);
    }

    public function test_cannot_complete_without_grant(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_START);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::IN_PROGRESS->value,
            'assigned_user_id' => $this->employee->id,
        ]);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.complete', $kaizen), ['actual_result' => 'Done'])
            ->assertForbidden();
    }

    public function test_idor_prevented_for_different_department(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept2->id,
            'status' => KaizenStatus::IN_PROGRESS->value,
            'assigned_user_id' => $this->employee->id,
        ]);

        $this->actingAs($this->opex)
            ->post(route('kaizens.implementation.complete', $kaizen), ['actual_result' => 'Done'])
            ->assertForbidden();
    }
}
