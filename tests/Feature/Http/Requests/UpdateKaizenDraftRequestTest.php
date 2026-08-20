<?php

namespace Tests\Feature\Http\Requests;

use App\Enums\KaizenPriority;
use App\Enums\KaizenStatus;
use App\Http\Requests\Kaizens\UpdateKaizenDraftRequest;
use App\Models\Category;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateKaizenDraftRequestTest extends TestCase
{
    use RefreshDatabase;

    private UpdateKaizenDraftRequest $request;

    private Kaizen $kaizen;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = User::factory()->create(['is_active' => true]);
        $this->kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->creator->id,
        ]);

        $this->request = new UpdateKaizenDraftRequest;

        // Mock the route parameters
        $this->request->setRouteResolver(function () {
            $route = new Route('PUT', '/kaizens/{kaizen}', []);
            $route->bind(Request::create('/kaizens/1', 'PUT'));
            $route->setParameter('kaizen', $this->kaizen);

            return $route;
        });

        $this->request->setUserResolver(fn () => $this->creator);
    }

    protected function tearDown(): void
    {
        unset($this->request);
        unset($this->kaizen);
        unset($this->creator);
        parent::tearDown();
    }

    private function validate(array $data): \Illuminate\Contracts\Validation\Validator
    {
        // Must set data on request for hasAny check
        $this->request->merge($data);
        $validator = Validator::make($data, $this->request->rules());
        $this->request->withValidator($validator);

        return $validator;
    }

    public function test_valid_single_field_update_passes(): void
    {
        $validator = $this->validate([
            'title' => 'Updated Title Here',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_valid_all_editable_fields_update_passes(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $validator = $this->validate([
            'category_id' => $category->id,
            'title' => 'Updated Title Here',
            'current_situation' => 'Updated current situation.',
            'proposed_situation' => 'Updated proposed situation.',
            'expected_benefit' => 'Updated expected benefit.',
            'priority' => KaizenPriority::HIGH->value,
            'target_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_empty_payload(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('payload'));
    }

    public function test_it_accepts_active_category(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $validator = $this->validate([
            'category_id' => $category->id,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_inactive_category(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $validator = $this->validate([
            'category_id' => $category->id,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category_id'));
    }

    public function test_it_rejects_non_existent_category(): void
    {
        $validator = $this->validate([
            'category_id' => 9999,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category_id'));
    }

    public function test_it_enforces_string_lengths(): void
    {
        $validator = $this->validate([
            'title' => 'abc', // < 5
            'current_situation' => 'short', // < 10
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
        $this->assertTrue($validator->errors()->has('current_situation'));
    }

    public function test_it_validates_priority_enum(): void
    {
        $validator = $this->validate([
            'priority' => 'INVALID_PRIORITY',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('priority'));
    }

    public function test_it_validates_target_date(): void
    {
        $validator = $this->validate([
            'target_date' => Carbon::yesterday()->format('Y-m-d'),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('target_date'));

        // Reset and check valid
        $this->request = new UpdateKaizenDraftRequest;
        $this->request->setRouteResolver(function () {
            $route = new Route('PUT', '/kaizens/{kaizen}', []);
            $route->bind(Request::create('/kaizens/1', 'PUT'));
            $route->setParameter('kaizen', $this->kaizen);

            return $route;
        });
        $this->request->setUserResolver(fn () => $this->creator);

        $validator = $this->validate([
            'target_date' => Carbon::today()->format('Y-m-d'),
        ]);
        $this->assertTrue($validator->passes());
    }

    public function test_nullable_fields_can_be_null(): void
    {
        $validator = $this->validate([
            'priority' => null,
            'target_date' => null,
            'expected_benefit' => null,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_sensitive_fields_are_prohibited(): void
    {
        $sensitiveFields = [
            'code' => 'KZN-01',
            'creator_user_id' => 2,
            'department_id' => 2,
            'assigned_user_id' => 2,
            'status' => 'APPROVED',
            'actual_result' => 'Result',
            'realized_benefit' => 'Benefit',
            'submitted_at' => now()->format('Y-m-d H:i:s'),
            'approved_at' => now()->format('Y-m-d H:i:s'),
            'started_at' => now()->format('Y-m-d H:i:s'),
            'completed_at' => now()->format('Y-m-d H:i:s'),
            'rejected_at' => now()->format('Y-m-d H:i:s'),
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];

        // Also add a valid field to pass the empty payload check
        $validator = $this->validate(array_merge($sensitiveFields, ['title' => 'Valid Title']));

        $this->assertTrue($validator->fails());

        foreach (array_keys($sensitiveFields) as $field) {
            $this->assertTrue($validator->errors()->has($field));
        }
    }

    public function test_authorize_returns_true_for_authorized_creator(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_authorize_returns_false_for_non_creator(): void
    {
        $otherUser = User::factory()->create(['is_active' => true]);
        $this->request->setUserResolver(fn () => $otherUser);

        $this->assertFalse($this->request->authorize());
    }

    public function test_authorize_returns_false_for_inactive_creator(): void
    {
        $this->creator->is_active = false;
        $this->creator->save();

        $this->assertFalse($this->request->authorize());
    }

    public function test_authorize_returns_false_if_not_draft_or_revision_requested(): void
    {
        $this->kaizen->status = KaizenStatus::SUBMITTED;
        $this->kaizen->save();

        $this->assertFalse($this->request->authorize());
    }

    public function test_authorize_returns_false_if_no_route_parameter(): void
    {
        $this->request->setRouteResolver(function () {
            $route = new Route('PUT', '/kaizens', []);
            $route->bind(Request::create('/kaizens', 'PUT'));

            return $route;
        });

        $this->assertFalse($this->request->authorize());
    }

    public function test_authorize_returns_false_if_route_parameter_is_not_kaizen_model(): void
    {
        $this->request->setRouteResolver(function () {
            $route = new Route('PUT', '/kaizens/{kaizen}', []);
            $route->bind(Request::create('/kaizens/123', 'PUT'));
            $route->setParameter('kaizen', '123'); // string instead of Kaizen model

            return $route;
        });

        $this->assertFalse($this->request->authorize());
    }
}
