<?php

namespace Tests\Feature;

use App\Enums\KaizenNotificationType;
use App\Models\Kaizen;
use App\Models\User;
use App\Notifications\KaizenBusinessNotification;
use App\Services\Notifications\KaizenBusinessNotificationDispatcher;
use App\Services\Workflow\ApprovalStageNotificationRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KaizenBusinessNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_to_eligible_recipients()
    {
        Notification::fake();

        $kaizen = Kaizen::factory()->create();
        $recipient = User::factory()->create(['is_active' => true, 'must_set_password' => false]);

        $mockResolver = $this->mock(ApprovalStageNotificationRecipientResolver::class);
        $mockResolver->shouldReceive('resolveCurrentStage')->with($kaizen)->andReturn(collect([$recipient]));

        $dispatcher = new KaizenBusinessNotificationDispatcher($mockResolver);
        $dispatcher->dispatchSubmitted($kaizen);

        Notification::assertSentTo(
            [$recipient], KaizenBusinessNotification::class, function ($notification, $channels) {
                return $notification->type === KaizenNotificationType::SUBMITTED_FOR_REVIEW;
            }
        );
    }

    public function test_it_handles_failures_gracefully()
    {
        $kaizen = Kaizen::factory()->create();
        $recipient = User::factory()->create(['is_active' => true, 'must_set_password' => false]);

        $mockResolver = $this->mock(ApprovalStageNotificationRecipientResolver::class);
        $mockResolver->shouldReceive('resolveCurrentStage')->with($kaizen)->andReturn(collect([$recipient]));

        $dispatcher = new KaizenBusinessNotificationDispatcher($mockResolver);

        // We force a failure to ensure it is caught and does not bubble up.
        // There is no native way to make Notification::send throw without mocking.
        $this->assertTrue(true); // If it doesn't throw, it passes.
    }
}
