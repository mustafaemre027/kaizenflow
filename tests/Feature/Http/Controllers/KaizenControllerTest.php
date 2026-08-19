<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\Kaizens\CreateKaizenDraft;
use App\Actions\Kaizens\UpdateKaizenDraft;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaizenControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->department = Department::factory()->create(['is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true, 'department_id' => $this->department->id]);
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    // ==========================================
    // CREATE METHOD TESTS
    // ==========================================

    public function test_create_unauthenticated_returns_302_redirect(): void
    {
        $response = $this->get(route('kaizens.create'));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_intended_redirect_works_for_kaizen_create(): void
    {
        // 1. Unauthenticated request to protected route
        $response = $this->get(route('kaizens.create'));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));

        // 2. Login with valid credentials
        $loginResponse = $this->post(route('login.store'), [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        // 3. Assert successful login and intended redirect
        $this->assertAuthenticatedAs($this->user);
        $loginResponse->assertRedirect(route('kaizens.create'));

        // 4. Assert following the redirect leads to the protected page
        $intendedResponse = $this->get(route('kaizens.create'));
        $intendedResponse->assertStatus(200);
        $intendedResponse->assertViewIs('kaizens.create');
    }

    public function test_create_authorized_user_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('kaizens.create'));

        $response->assertStatus(200);
        $response->assertViewIs('kaizens.create');
        $response->assertViewHas('categories');

        $categories = $response->viewData('categories');
        $this->assertTrue($categories->contains($this->category));

        $inactiveCategory = Category::factory()->create(['is_active' => false]);
        $response = $this->actingAs($this->user)->get(route('kaizens.create'));
        $categories = $response->viewData('categories');
        $this->assertFalse($categories->contains($inactiveCategory));
    }

    public function test_create_unauthorized_user_returns_403(): void
    {
        $this->user->is_active = false;
        $this->user->save();

        $response = $this->actingAs($this->user)->get(route('kaizens.create'));
        $response->assertStatus(403);
    }

    // ==========================================
    // STORE METHOD TESTS
    // ==========================================

    public function test_store_unauthenticated_json_returns_401(): void
    {
        $response = $this->postJson(route('kaizens.store'), []);
        $response->assertStatus(401);
    }

    public function test_store_authorized_active_user_can_create_draft(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Controller Test Kaizen',
            'current_situation' => 'Current sit',
            'proposed_situation' => 'Proposed sit',
            'expected_benefit' => 'Expected ben',
        ];

        $response = $this->actingAs($this->user)->postJson(route('kaizens.store'), $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id', 'code', 'title', 'status', 'category_id', 'department_id', 'priority', 'target_date',
        ]);

        $response->assertJsonMissing(['email', 'password', 'remember_token', 'created_at', 'updated_at']);

        $kaizenId = $response->json('id');
        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizenId,
            'creator_user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'status' => KaizenStatus::DRAFT->value,
            'assigned_user_id' => null,
            'title' => 'Controller Test Kaizen',
        ]);

        $kaizen = Kaizen::find($kaizenId);
        $this->assertMatchesRegularExpression('/^KZN-\d{4}-\d{6,}$/', $kaizen->code);
    }

    public function test_store_sensitive_field_injection_returns_422(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Controller Test Kaizen',
            'status' => 'APPROVED',
            'code' => 'HACKED-CODE',
        ];

        $response = $this->actingAs($this->user)->postJson(route('kaizens.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status', 'code']);
    }

    public function test_store_inactive_user_returns_403(): void
    {
        $this->user->is_active = false;
        $this->user->save();

        $payload = ['category_id' => $this->category->id, 'title' => 'Test'];
        $response = $this->actingAs($this->user)->postJson(route('kaizens.store'), $payload);
        $response->assertStatus(403);
    }

    public function test_store_user_without_department_returns_403(): void
    {
        $this->user->department_id = null;
        $this->user->save();

        $payload = ['category_id' => $this->category->id, 'title' => 'Test'];
        $response = $this->actingAs($this->user)->postJson(route('kaizens.store'), $payload);
        $response->assertStatus(403);
    }

    public function test_store_user_with_inactive_department_returns_403(): void
    {
        $this->department->is_active = false;
        $this->department->save();

        $payload = ['category_id' => $this->category->id, 'title' => 'Test'];
        $response = $this->actingAs($this->user)->postJson(route('kaizens.store'), $payload);
        $response->assertStatus(403);
    }

    public function test_store_inactive_category_returns_422(): void
    {
        $this->category->is_active = false;
        $this->category->save();

        $payload = ['category_id' => $this->category->id, 'title' => 'Test'];
        $response = $this->actingAs($this->user)->postJson(route('kaizens.store'), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    public function test_store_invalid_payload_returns_422(): void
    {
        $payload = ['title' => ''];
        $response = $this->actingAs($this->user)->postJson(route('kaizens.store'), $payload);
        $response->assertStatus(422);
    }

    public function test_store_html_request_redirects_to_show_with_flash_message(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'HTML Test Kaizen',
            'current_situation' => 'Current sit',
            'proposed_situation' => 'Proposed sit',
            'expected_benefit' => 'Expected ben',
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $kaizen = Kaizen::where('title', 'HTML Test Kaizen')->firstOrFail();

        $response->assertRedirect(route('kaizens.show', $kaizen));
        $response->assertSessionHas('success', 'Kaizen taslağı başarıyla oluşturuldu.');
    }

    public function test_store_uses_create_action(): void
    {
        $mock = $this->mock(CreateKaizenDraft::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(Kaizen::factory()->make(['id' => 1]));
        });

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Action Mock Test',
            'current_situation' => 'Mock situation long enough',
            'proposed_situation' => 'Mock situation long enough',
            'expected_benefit' => 'Mock benefit long enough',
        ];

        $this->actingAs($this->user)->postJson(route('kaizens.store'), $payload);
    }

    // ==========================================
    // EDIT METHOD TESTS
    // ==========================================

    public function test_edit_unauthenticated_returns_302_redirect(): void
    {
        $kaizen = Kaizen::factory()->create();
        $response = $this->get(route('kaizens.edit', $kaizen));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_edit_creator_can_view_draft(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('kaizens.edit', $kaizen));

        $response->assertStatus(200);
        $response->assertViewIs('kaizens.edit');
        $response->assertSee($kaizen->title);
        $response->assertSee($kaizen->current_situation);
    }

    public function test_edit_creator_can_view_revision_requested(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::REVISION_REQUESTED)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('kaizens.edit', $kaizen));
        $response->assertStatus(200);
    }

    public function test_edit_other_user_returns_403(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create();

        $response = $this->actingAs($this->user)->get(route('kaizens.edit', $kaizen));
        $response->assertStatus(403);
    }

    public function test_edit_non_editable_status_returns_403(): void
    {
        $statuses = [
            KaizenStatus::SUBMITTED,
            KaizenStatus::APPROVED,
            KaizenStatus::IN_PROGRESS,
            KaizenStatus::COMPLETED,
            KaizenStatus::REJECTED,
            KaizenStatus::MANAGER_REVIEW,
        ];

        foreach ($statuses as $status) {
            $kaizen = Kaizen::factory()->withStatus($status)->create([
                'creator_user_id' => $this->user->id,
            ]);

            $response = $this->actingAs($this->user)->get(route('kaizens.edit', $kaizen));
            $response->assertStatus(403);
        }
    }

    // ==========================================
    // UPDATE METHOD TESTS
    // ==========================================

    public function test_update_unauthenticated_json_returns_401(): void
    {
        $kaizen = Kaizen::factory()->create();
        $response = $this->patchJson(route('kaizens.update', $kaizen), []);
        $response->assertStatus(401);
    }

    public function test_update_creator_can_update_draft(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $payload = ['title' => 'Updated Controller Title'];

        $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'code', 'title', 'status', 'category_id', 'department_id', 'priority', 'target_date',
        ]);

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'title' => 'Updated Controller Title',
        ]);
    }

    public function test_update_creator_can_update_revision_requested(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::REVISION_REQUESTED)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $payload = ['title' => 'Updated Rev Title'];

        $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);
        $response->assertStatus(200);
    }

    public function test_update_partial_update_preserves_other_fields(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
            'title' => 'Original Title',
            'current_situation' => 'Original Sit',
        ]);

        $payload = ['title' => 'New Title'];
        $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'title' => 'New Title',
            'current_situation' => 'Original Sit',
        ]);
    }

    public function test_update_sensitive_field_injection_returns_422(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $payload = [
            'title' => 'Valid Title',
            'status' => 'APPROVED',
            'creator_user_id' => 999,
        ];

        $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status', 'creator_user_id']);
    }

    public function test_update_non_creator_returns_403(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create(); // Different creator
        $payload = ['title' => 'Updated Title'];
        $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);
        $response->assertStatus(403);
    }

    public function test_update_inactive_creator_returns_403(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $this->user->is_active = false;
        $this->user->save();

        $payload = ['title' => 'Updated Title'];
        $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);
        $response->assertStatus(403);
    }

    public function test_update_other_statuses_return_403(): void
    {
        $statuses = [
            KaizenStatus::SUBMITTED,
            KaizenStatus::APPROVED,
            KaizenStatus::IN_PROGRESS,
            KaizenStatus::COMPLETED,
            KaizenStatus::REJECTED,
            KaizenStatus::MANAGER_REVIEW,
        ];

        foreach ($statuses as $status) {
            $kaizen = Kaizen::factory()->withStatus($status)->create([
                'creator_user_id' => $this->user->id,
            ]);

            $payload = ['title' => 'Updated Title'];
            $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);
            $response->assertStatus(403);
        }
    }

    public function test_update_non_existent_kaizen_returns_404(): void
    {
        $response = $this->actingAs($this->user)->patchJson('/kaizens/99999', ['title' => 'New']);
        $response->assertStatus(404);
    }

    public function test_update_inactive_category_returns_422(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $inactiveCategory = Category::factory()->create(['is_active' => false]);

        $payload = ['category_id' => $inactiveCategory->id];
        $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);
        $response->assertStatus(422);
    }

    public function test_update_html_request_redirects_to_show_with_flash_message(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $payload = ['title' => 'HTML Title'];

        $response = $this->from('/edit-kaizen')->actingAs($this->user)->patch(route('kaizens.update', $kaizen), $payload);
        $response->assertRedirect(route('kaizens.show', $kaizen));
        $response->assertSessionHas('success', 'Kaizen taslağı başarıyla güncellendi.');
    }

    public function test_update_preserves_sensitive_database_columns(): void
    {
        $otherUser = User::factory()->create();

        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'code' => 'KZN-2026-999999',
            'assigned_user_id' => $otherUser->id,
        ]);

        $payload = ['title' => 'New Safe Title'];

        $response = $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'code' => 'KZN-2026-999999',
            'creator_user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'assigned_user_id' => $otherUser->id,
            'status' => KaizenStatus::DRAFT->value,
            'title' => 'New Safe Title',
        ]);
    }

    public function test_update_uses_update_action(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $mock = $this->mock(UpdateKaizenDraft::class, function ($mock) use ($kaizen) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn($kaizen);
        });

        $payload = ['title' => 'Action Mock Title'];
        $this->actingAs($this->user)->patchJson(route('kaizens.update', $kaizen), $payload);
    }

    // ==========================================
    // SHOW METHOD TESTS
    // ==========================================

    public function test_show_unauthenticated_returns_302_redirect(): void
    {
        $kaizen = Kaizen::factory()->create();
        $response = $this->get(route('kaizens.show', $kaizen));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_show_authorized_creator_returns_200(): void
    {
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->user->id,
            'title' => 'My Kaizen Title',
            'current_situation' => 'Current sit',
            'proposed_situation' => 'Proposed sit',
            'expected_benefit' => 'Expected ben',
        ]);

        $response = $this->actingAs($this->user)->get(route('kaizens.show', $kaizen));

        $response->assertStatus(200);
        $response->assertViewIs('kaizens.show');
        $response->assertSee('My Kaizen Title');
        $response->assertSee('Current sit');
        $response->assertSee('Proposed sit');
        $response->assertSee('Expected ben');
        $response->assertSee($kaizen->code);
    }

    public function test_show_unauthorized_user_returns_403(): void
    {
        $otherUser = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->create(); // creator is another user by default

        $response = $this->actingAs($otherUser)->get(route('kaizens.show', $kaizen));
        $response->assertStatus(403);
    }

    public function test_show_eager_loads_relations_and_handles_null_states(): void
    {
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->user->id,
            'assigned_user_id' => null,
            'target_date' => null,
            'submitted_at' => null,
            'actual_result' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('kaizens.show', $kaizen));

        $response->assertStatus(200);
        $response->assertSee('Atanmadı');
        $response->assertSee('Belirtilmedi');
        $response->assertSee('Henüz gönderilmedi');
    }

    public function test_show_renders_special_state_revision_requested(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::REVISION_REQUESTED)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('kaizens.show', $kaizen));

        $response->assertStatus(200);
        $response->assertSee('Revizyon İstendi');
    }

    public function test_show_renders_special_state_rejected(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::REJECTED)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('kaizens.show', $kaizen));

        $response->assertStatus(200);
        $response->assertSee('Reddedildi');
    }

    // ==========================================
    // INDEX METHOD TESTS
    // ==========================================

    public function test_index_unauthenticated_returns_302_redirect(): void
    {
        $response = $this->get(route('kaizens.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_index_employee_can_only_see_own_or_assigned_kaizens(): void
    {
        $this->user->role = UserRole::EMPLOYEE;
        $this->user->save();

        $otherUser = User::factory()->create();

        $ownKaizen = Kaizen::factory()->create(['creator_user_id' => $this->user->id]);
        $assignedKaizen = Kaizen::factory()->create(['assigned_user_id' => $this->user->id]);
        $unrelatedKaizen = Kaizen::factory()->create(['creator_user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->get(route('kaizens.index'));
        $response->assertStatus(200);
        $response->assertViewHas('kaizens');

        $kaizens = $response->viewData('kaizens');
        $this->assertTrue($kaizens->contains($ownKaizen));
        $this->assertTrue($kaizens->contains($assignedKaizen));
        $this->assertFalse($kaizens->contains($unrelatedKaizen));
    }

    public function test_index_manager_can_see_own_assigned_and_department_kaizens(): void
    {
        $this->user->role = UserRole::MANAGER;
        $this->user->save();

        $otherDepartment = Department::factory()->create();
        $otherUser = User::factory()->create(['department_id' => $otherDepartment->id]);

        $departmentKaizen = Kaizen::factory()->create(['department_id' => $this->department->id, 'creator_user_id' => $otherUser->id]);
        $unrelatedKaizen = Kaizen::factory()->create(['department_id' => $otherDepartment->id, 'creator_user_id' => $otherUser->id]);
        $crossDepartmentOwnKaizen = Kaizen::factory()->create(['department_id' => $otherDepartment->id, 'creator_user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('kaizens.index'));

        $kaizens = $response->viewData('kaizens');
        $this->assertTrue($kaizens->contains($departmentKaizen));
        $this->assertTrue($kaizens->contains($crossDepartmentOwnKaizen));
        $this->assertFalse($kaizens->contains($unrelatedKaizen));
    }

    public function test_index_opex_specialist_can_see_all_kaizens(): void
    {
        $this->user->role = UserRole::OPEX_SPECIALIST;
        $this->user->save();

        $unrelatedKaizen = Kaizen::factory()->create();

        $response = $this->actingAs($this->user)->get(route('kaizens.index'));
        $kaizens = $response->viewData('kaizens');
        $this->assertTrue($kaizens->contains($unrelatedKaizen));
    }

    public function test_index_admin_can_see_all_kaizens(): void
    {
        $this->user->role = UserRole::ADMIN;
        $this->user->save();

        $unrelatedKaizen = Kaizen::factory()->create();

        $response = $this->actingAs($this->user)->get(route('kaizens.index'));
        $kaizens = $response->viewData('kaizens');
        $this->assertTrue($kaizens->contains($unrelatedKaizen));
    }

    public function test_index_search_filters_correctly_and_does_not_bypass_authorization(): void
    {
        $this->user->role = UserRole::EMPLOYEE;
        $this->user->save();

        $matchingOwn = Kaizen::factory()->create(['creator_user_id' => $this->user->id, 'title' => 'Match Title']);
        $nonMatchingOwn = Kaizen::factory()->create(['creator_user_id' => $this->user->id, 'title' => 'Other Title']);
        $matchingUnrelated = Kaizen::factory()->create(['title' => 'Match Title']);

        $response = $this->actingAs($this->user)->get(route('kaizens.index', ['q' => 'Match']));

        $kaizens = $response->viewData('kaizens');
        $this->assertTrue($kaizens->contains($matchingOwn));
        $this->assertFalse($kaizens->contains($nonMatchingOwn));
        $this->assertFalse($kaizens->contains($matchingUnrelated));
    }

    public function test_index_filters_work_and_do_not_bypass_authorization(): void
    {
        $this->user->role = UserRole::EMPLOYEE;
        $this->user->save();

        $otherDept = Department::factory()->create();

        $matchingOwn = Kaizen::factory()->create(['creator_user_id' => $this->user->id, 'department_id' => $this->department->id, 'status' => KaizenStatus::DRAFT]);
        $unrelatedMatchFilter = Kaizen::factory()->create(['department_id' => $otherDept->id, 'status' => KaizenStatus::DRAFT]);

        $response = $this->actingAs($this->user)->get(route('kaizens.index', [
            'status' => KaizenStatus::DRAFT->value,
            'department_id' => $otherDept->id,
        ]));

        $kaizens = $response->viewData('kaizens');
        $this->assertFalse($kaizens->contains($unrelatedMatchFilter));
        $this->assertFalse($kaizens->contains($matchingOwn));
    }

    public function test_index_sort_validation(): void
    {
        $response = $this->actingAs($this->user)->get(route('kaizens.index', ['sort' => 'created_at', 'direction' => 'desc']));
        $response->assertStatus(200);

        $invalidResponse = $this->actingAs($this->user)->get(route('kaizens.index', ['sort' => 'invalid_column']), ['Accept' => 'application/json']);
        $invalidResponse->assertStatus(422);
    }

    public function test_index_pagination_returns_correct_number_of_items(): void
    {
        $this->user->role = UserRole::ADMIN;
        $this->user->save();

        Kaizen::factory()->count(20)->create();

        $response = $this->actingAs($this->user)->get(route('kaizens.index'));
        $kaizens = $response->viewData('kaizens');

        $this->assertCount(15, $kaizens->items());
        $this->assertTrue($kaizens->hasPages());
    }
}
