<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaizenImplementationUITest extends TestCase
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

        $this->admin = User::factory()->create(['department_id' => $this->dept1->id, 'role' => UserRole::ADMIN]);
        $this->manager = User::factory()->create(['department_id' => $this->dept1->id, 'role' => UserRole::MANAGER]);
        $this->opex = User::factory()->create(['department_id' => $this->dept1->id, 'role' => UserRole::OPEX_SPECIALIST]);
        $this->employee = User::factory()->create(['department_id' => $this->dept1->id, 'role' => UserRole::EMPLOYEE]);
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

    public function test_assign_form_visible_for_authorized_user_and_shows_candidates(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);

        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'category_id' => $this->category->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => null,
        ]);

        $activeSameDept = User::factory()->create(['department_id' => $this->dept1->id, 'is_active' => true, 'name' => 'Active John']);
        $inactiveSameDept = User::factory()->create(['department_id' => $this->dept1->id, 'is_active' => false, 'name' => 'Inactive Bob']);
        $diffDept = User::factory()->create(['department_id' => $this->dept2->id, 'is_active' => true, 'name' => 'Diff Dept Alice']);

        $response = $this->actingAs($this->opex)->get(route('kaizens.show', $kaizen));

        $response->assertOk();
        $response->assertSee('Uygulama Yönetimi');
        $response->assertSee('Sorumlu Ata');
        $response->assertSee(route('kaizens.implementation.assign', $kaizen));

        // See active user in same dept
        $response->assertSee($activeSameDept->name);

        // Do not see inactive or diff dept users
        $response->assertDontSee($inactiveSameDept->name);
        $response->assertDontSee($diffDept->name);
    }

    public function test_start_form_visible_for_authorized_user(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_START);

        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'category_id' => $this->category->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => $this->employee->id,
            'target_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->opex)->get(route('kaizens.show', $kaizen));

        $response->assertOk();
        $response->assertSee('Uygulamayı Başlat');
        $response->assertSee(route('kaizens.implementation.start', $kaizen));
    }

    public function test_complete_form_visible_for_authorized_user(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE);

        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'category_id' => $this->category->id,
            'status' => KaizenStatus::IN_PROGRESS->value,
            'assigned_user_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->opex)->get(route('kaizens.show', $kaizen));

        $response->assertOk();
        $response->assertSee('Uygulamayı Tamamla');
        $response->assertSee(route('kaizens.implementation.complete', $kaizen));
        $response->assertSee('maxlength="5000"', false);
    }

    public function test_forms_not_visible_for_unauthorized_users(): void
    {
        // No grant
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('kaizens.show', $kaizen));
        $response->assertOk();
        $response->assertDontSee('Sorumlu Ata');
        $response->assertDontSee(route('kaizens.implementation.assign', $kaizen));

        // Active grant but wrong department
        $this->grant($this->opex, $this->dept2, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);
        $response = $this->actingAs($this->opex)->get(route('kaizens.show', $kaizen));
        $response->assertDontSee('Sorumlu Ata');

        // Historical reviewer check (using a plain user)
        // Just being creator or related doesn't give rights
        $response = $this->actingAs($kaizen->creator)->get(route('kaizens.show', $kaizen));
        $response->assertDontSee('Sorumlu Ata');

        // Assignee alone doesn't have start/complete without grant
        $kaizenAssigned = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => $this->employee->id,
            'target_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->employee)->get(route('kaizens.show', $kaizenAssigned));
        $response->assertDontSee('Uygulamayı Başlat');
    }

    public function test_forms_not_visible_in_wrong_lifecycle_states(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_START);
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE);

        // COMPLETED state should show NO forms
        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::COMPLETED->value,
            'assigned_user_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->opex)->get(route('kaizens.show', $kaizen));
        $response->assertDontSee('Sorumlu Ata');
        $response->assertDontSee('Uygulamayı Başlat');
        $response->assertDontSee('Uygulamayı Tamamla');

        // DRAFT state should show NO forms
        $kaizenDraft = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::DRAFT->value,
            'assigned_user_id' => null,
        ]);

        $response = $this->actingAs($this->opex)->get(route('kaizens.show', $kaizenDraft));
        $response->assertDontSee('Sorumlu Ata');
    }

    public function test_assign_form_not_visible_if_already_assigned(): void
    {
        $this->grant($this->opex, $this->dept1, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN);

        $kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept1->id,
            'status' => KaizenStatus::APPROVED->value,
            'assigned_user_id' => $this->employee->id,
            'target_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->opex)->get(route('kaizens.show', $kaizen));
        $response->assertDontSee('Sorumlu Ata');
        $response->assertDontSee(route('kaizens.implementation.assign', $kaizen));
    }
}
