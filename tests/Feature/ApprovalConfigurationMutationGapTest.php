<?php

namespace Tests\Feature;

use App\Actions\ApprovalConfiguration\DeactivateApprovalWorkflow;
use App\Enums\UserCapability;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApprovalConfigurationMutationGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivate_does_not_use_aggregate_lock_query()
    {
        $user = User::factory()->create(["is_active" => true]);
        UserSystemCapabilityGrant::create([
            "user_id" => $user->id,
            "capability" => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            "is_active" => true,
        ]);

        $workflow = ApprovalWorkflow::factory()->create(["is_default" => false, "published_at" => now(), "is_active" => true]);

        DB::enableQueryLog();

        $action = $this->app->make(DeactivateApprovalWorkflow::class);
        $action->execute($user, $workflow);

        $queries = DB::getQueryLog();
        $hasAggregate = false;
        foreach ($queries as $query) {
            $sql = strtolower($query["query"]);
            if (str_contains($sql, "aggregate") && str_contains($sql, "kaizen_workflow_instances")) {
                $hasAggregate = true;
                break;
            }
        }

        $this->assertFalse($hasAggregate, "Aggregate queries like count() with lockForUpdate() are not allowed.");
    }
}

