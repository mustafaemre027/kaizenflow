<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\Kaizens\CreateKaizenDraft;
use App\Actions\Kaizens\UpdateKaizenDraft;
use App\Enums\KaizenStatus;
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

    public function test_store_html_request_redirects_back_with_flash_message(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'HTML Test Kaizen',
            'current_situation' => 'Current sit',
            'proposed_situation' => 'Proposed sit',
            'expected_benefit' => 'Expected ben',
        ];

        // Simulate a request coming from /home
        $response = $this->from('/home')->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertRedirect('/home');
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

    public function test_update_html_request_redirects_back_with_flash_message(): void
    {
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->user->id,
        ]);

        $payload = ['title' => 'HTML Title'];

        $response = $this->from('/edit-kaizen')->actingAs($this->user)->patch(route('kaizens.update', $kaizen), $payload);
        $response->assertRedirect('/edit-kaizen');
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
}
