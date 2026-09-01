<?php

namespace Tests\Feature;

use App\Actions\Kaizens\SubmitKaizen;
use App\Enums\KaizenStatus;
use App\Exceptions\Workflow\InvalidApprovalWorkflowConfiguration;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\Notifications\KaizenBusinessNotificationDispatcher;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionNotificationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_kaizen_dispatches_notification_after_commit()
    {
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['creator_user_id' => $user->id, 'status' => KaizenStatus::DRAFT]);
        $workflow = ApprovalWorkflow::factory()->create(['is_active' => true, 'published_at' => now(), 'is_default' => true]);
        ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 1, 'is_final' => true]);

        $dispatcher = $this->mock(KaizenBusinessNotificationDispatcher::class);
        $dispatcher->shouldReceive('dispatchSubmitted')->once();

        app(SubmitKaizen::class)->execute($user, $kaizen);
    }

    public function test_submit_kaizen_no_dispatch_on_failure()
    {
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['creator_user_id' => $user->id, 'status' => KaizenStatus::DRAFT]);
        // No active workflow will cause exception

        $dispatcher = $this->mock(KaizenBusinessNotificationDispatcher::class);
        $dispatcher->shouldNotReceive('dispatchSubmitted');

        $this->expectException(InvalidApprovalWorkflowConfiguration::class);
        app(SubmitKaizen::class)->execute($user, $kaizen);
    }
}
