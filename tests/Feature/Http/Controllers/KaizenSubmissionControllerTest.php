<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\Kaizens\SubmitKaizen;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class KaizenSubmissionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_json_user_receives_401(): void
    {
        $kaizen = Kaizen::factory()->create();

        $response = $this->postJson(route('kaizens.submit', $kaizen));

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_html_user_is_redirected_to_login(): void
    {
        $this->app['router']->get('login', ['as' => 'login', 'uses' => fn () => 'login']);

        $kaizen = Kaizen::factory()->create();

        $response = $this->post(route('kaizens.submit', $kaizen));

        $response->assertRedirect(route('login'));
    }

    public function test_active_employee_can_submit_own_draft_kaizen(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen), [
            'reason' => '   Some reason   ',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'status' => KaizenStatus::SUBMITTED->value,
        ]);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $user->id,
            'transition_code' => 'TR-001',
            'reason' => 'Some reason',
        ]);

        $this->assertNotNull($kaizen->fresh()->submitted_at);
    }

    public function test_active_employee_can_resubmit_revision_requested_kaizen(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::REVISION_REQUESTED,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertOk();

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'status' => KaizenStatus::SUBMITTED->value,
        ]);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $user->id,
            'transition_code' => 'TR-002',
            'reason' => null,
        ]);
    }

    public function test_empty_reason_is_stored_as_null(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen), [
            'reason' => '',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'reason' => null,
        ]);
    }

    public function test_reason_with_2000_characters_is_accepted(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $reason = Str::random(2000);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen), [
            'reason' => $reason,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'reason' => $reason,
        ]);
    }

    public function test_reason_with_2001_characters_produces_422(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $reason = Str::random(2001);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen), [
            'reason' => $reason,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('reason');
    }

    public function test_user_cannot_submit_other_users_kaizen(): void
    {
        $owner = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $otherUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $owner->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($otherUser)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();
    }

    public function test_inactive_user_cannot_submit(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => false]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();
    }

    public function test_opex_specialist_cannot_submit(): void
    {
        $user = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();
    }

    public function test_manager_cannot_submit(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();
    }

    public function test_admin_does_not_get_automatic_bypass(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();
    }

    public function test_submitted_kaizen_cannot_be_resubmitted(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();
    }

    public function test_completed_kaizen_cannot_be_submitted(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::COMPLETED,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();
    }

    public function test_rejected_kaizen_cannot_be_submitted(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::REJECTED,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();
    }

    public function test_unauthorized_request_does_not_change_status_or_create_history(): void
    {
        $owner = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $otherUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $owner->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($otherUser)->postJson(route('kaizens.submit', $kaizen));

        $response->assertForbidden();

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'status' => KaizenStatus::DRAFT->value,
        ]);
        $this->assertDatabaseCount('kaizen_status_histories', 0);
    }

    public function test_prohibited_fields_are_rejected_with_422(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $prohibitedFields = [
            'id', 'code', 'creator_user_id', 'department_id', 'category_id',
            'assigned_user_id', 'title', 'current_situation', 'proposed_situation',
            'expected_benefit', 'actual_result', 'realized_benefit', 'status',
            'priority', 'target_date', 'submitted_at', 'approved_at', 'started_at',
            'completed_at', 'rejected_at', 'actor_user_id', 'transition_code',
            'from_status', 'to_status', 'metadata', 'created_at', 'updated_at',
        ];

        foreach ($prohibitedFields as $field) {
            $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen), [
                $field => 'some_value',
            ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors($field);
        }
    }

    public function test_json_response_contains_only_safe_fields(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'kaizen' => [
                'id',
                'code',
                'status',
                'submitted_at',
            ],
        ]);

        $json = $response->json();
        $this->assertEquals('Kaizen başarıyla gönderildi.', $json['message']);

        $this->assertArrayNotHasKey('email', $json);
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('remember_token', $json);
        $this->assertArrayNotHasKey('creator_user_id', $json['kaizen']);
        $this->assertArrayNotHasKey('assigned_user_id', $json['kaizen']);
        $this->assertArrayNotHasKey('metadata', $json['kaizen']);
        $this->assertArrayNotHasKey('actual_result', $json['kaizen']);
        $this->assertArrayNotHasKey('realized_benefit', $json['kaizen']);
    }

    public function test_html_request_redirects_back_with_success_message(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->from('/previous-page')->post(route('kaizens.submit', $kaizen));

        $response->assertRedirect('/previous-page');
        $response->assertSessionHas('success', 'Kaizen başarıyla gönderildi.');
    }

    public function test_route_has_auth_middleware(): void
    {
        $route = Route::getRoutes()->getByName('kaizens.submit');
        $this->assertNotNull($route);

        $middlewares = $route->gatherMiddleware();

        // Assert it has standard web middleware
        $this->assertContains('web', $middlewares);
        // Assert it has auth middleware
        $this->assertTrue(in_array('auth', $middlewares) || in_array('auth:web', $middlewares));
    }

    public function test_controller_delegates_to_action(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $user->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $mockAction = $this->mock(SubmitKaizen::class);
        $mockAction->shouldReceive('execute')
            ->once()
            ->andReturn($kaizen);

        $response = $this->actingAs($user)->postJson(route('kaizens.submit', $kaizen));

        $response->assertOk();
    }
}
