<?php

namespace App\Services\Notifications;

use App\Enums\KaizenNotificationType;
use App\Models\Kaizen;
use App\Models\User;
use App\Notifications\KaizenBusinessNotification;
use App\Services\Workflow\ApprovalStageNotificationRecipientResolver;
use Illuminate\Support\Facades\Log;

class KaizenBusinessNotificationDispatcher
{
    public function __construct(
        private readonly ApprovalStageNotificationRecipientResolver $recipientResolver
    ) {}

    public function dispatchSubmitted(Kaizen $kaizen): void
    {
        $recipients = $this->recipientResolver->resolveCurrentStage($kaizen);
        $this->dispatchToMany($recipients, KaizenNotificationType::SUBMITTED_FOR_REVIEW, $kaizen);
    }

    public function dispatchApprovalStageReady(Kaizen $kaizen): void
    {
        $recipients = $this->recipientResolver->resolveCurrentStage($kaizen);
        $this->dispatchToMany($recipients, KaizenNotificationType::APPROVAL_STAGE_READY, $kaizen);
    }

    public function dispatchRevisionRequested(Kaizen $kaizen): void
    {
        $this->dispatchToCreator($kaizen, KaizenNotificationType::REVISION_REQUESTED);
    }

    public function dispatchRejected(Kaizen $kaizen): void
    {
        $this->dispatchToCreator($kaizen, KaizenNotificationType::REJECTED);
    }

    public function dispatchApproved(Kaizen $kaizen): void
    {
        $this->dispatchToCreator($kaizen, KaizenNotificationType::APPROVED);
    }

    public function dispatchImplementationAssigned(Kaizen $kaizen): void
    {
        if (! $kaizen->assigned_user_id) {
            return;
        }

        $assignee = User::find($kaizen->assigned_user_id);
        if ($this->isEligibleForEmail($assignee)) {
            $this->dispatchToOne($assignee, KaizenNotificationType::IMPLEMENTATION_ASSIGNED, $kaizen, $kaizen->target_date?->format('Y-m-d'));
        }
    }

    public function dispatchImplementationStarted(Kaizen $kaizen, User $actor): void
    {
        if ($actor->id === $kaizen->creator_user_id) {
            return;
        }
        $this->dispatchToCreator($kaizen, KaizenNotificationType::IMPLEMENTATION_STARTED);
    }

    public function dispatchImplementationCompleted(Kaizen $kaizen, User $actor): void
    {
        if ($actor->id === $kaizen->creator_user_id) {
            return;
        }
        $this->dispatchToCreator($kaizen, KaizenNotificationType::IMPLEMENTATION_COMPLETED);
    }

    private function dispatchToCreator(Kaizen $kaizen, KaizenNotificationType $type): void
    {
        $creator = User::find($kaizen->creator_user_id);
        if ($this->isEligibleForEmail($creator)) {
            $this->dispatchToOne($creator, $type, $kaizen);
        }
    }

    private function isEligibleForEmail(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->is_active && ! $user->must_set_password && $user->email_verified_at !== null;
    }

    /**
     * @param  iterable<User>  $recipients
     */
    private function dispatchToMany(iterable $recipients, KaizenNotificationType $type, Kaizen $kaizen): void
    {
        foreach ($recipients as $recipient) {
            $this->dispatchToOne($recipient, $type, $kaizen);
        }
    }

    private function dispatchToOne(User $recipient, KaizenNotificationType $type, Kaizen $kaizen, ?string $targetDate = null): void
    {
        try {
            $recipient->notify(new KaizenBusinessNotification(
                type: $type,
                kaizenId: $kaizen->id,
                kaizenCode: $kaizen->code,
                kaizenTitle: $kaizen->title,
                targetDate: $targetDate
            ));
        } catch (\Throwable $e) {
            Log::error('Queue dispatch failed for Kaizen business notification', [
                'type' => $type->value,
                'kaizen_id' => $kaizen->id,
                'recipient_user_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
