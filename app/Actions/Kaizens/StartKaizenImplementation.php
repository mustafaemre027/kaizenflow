<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\KaizenStatusHistory;
use App\Models\User;
use App\Services\Notifications\KaizenBusinessNotificationDispatcher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class StartKaizenImplementation
{
    public function __construct(
        private readonly KaizenBusinessNotificationDispatcher $dispatcher
    ) {}

    public function execute(Kaizen $kaizen, User $actor): Kaizen
    {
        if (! $actor->can('startImplementation', $kaizen)) {
            throw new AuthorizationException('You are not authorized to start implementation for this Kaizen.');
        }

        if ($kaizen->status !== KaizenStatus::APPROVED) {
            throw new \DomainException('Only APPROVED Kaizens can be started.');
        }

        if (! $kaizen->assigned_user_id) {
            throw new \DomainException('Kaizen must have an assignee before starting implementation.');
        }

        if (! $kaizen->target_date) {
            throw new \DomainException('Kaizen must have a target date before starting implementation.');
        }

        $assignee = User::find($kaizen->assigned_user_id);
        if (! $assignee || ! $assignee->is_active) {
            throw new \DomainException('The assigned user is not active.');
        }

        $result = DB::transaction(function () use ($kaizen, $actor) {
            $lockedKaizen = Kaizen::where('id', $kaizen->id)->lockForUpdate()->first();

            if ($lockedKaizen->status !== KaizenStatus::APPROVED) {
                throw new \DomainException('Only APPROVED Kaizens can be started.');
            }

            $lockedKaizen->status = KaizenStatus::IN_PROGRESS;
            $lockedKaizen->started_at = now();
            $lockedKaizen->save();

            // Create status history entry
            KaizenStatusHistory::create([
                'kaizen_id' => $lockedKaizen->id,
                'from_status' => KaizenStatus::APPROVED->value,
                'to_status' => KaizenStatus::IN_PROGRESS->value,
                'transition_code' => 'START_IMPLEMENTATION',
                'actor_user_id' => $actor->id,
            ]);

            return $lockedKaizen;
        });

        $this->dispatcher->dispatchImplementationStarted($result, $actor);

        return $result;
    }
}
