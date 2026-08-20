<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\KaizenStatusHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssignKaizenImplementation
{
    public function execute(Kaizen $kaizen, User $actor, int $assigneeId, string $targetDate): Kaizen
    {
        if (! $actor->can('assignImplementation', $kaizen)) {
            throw new AuthorizationException('You are not authorized to assign implementation for this Kaizen.');
        }

        if ($actor->id === $assigneeId) {
            throw new AuthorizationException('You cannot assign yourself as the implementer.');
        }

        if ($kaizen->status !== KaizenStatus::APPROVED) {
            throw new \Exception('Only APPROVED Kaizens can be assigned.');
        }

        if ($kaizen->assigned_user_id) {
            throw new \Exception('Kaizen already has an active implementation assignment.');
        }

        $assignee = User::findOrFail($assigneeId);
        if (! $assignee->is_active) {
            throw new \Exception('The assigned user is not active.');
        }

        $target = Carbon::parse($targetDate);
        if ($target->isBefore(now()->startOfDay())) {
            throw new \InvalidArgumentException('Target date cannot be in the past.');
        }

        return DB::transaction(function () use ($kaizen, $actor, $assigneeId, $targetDate) {
            $lockedKaizen = Kaizen::where('id', $kaizen->id)->lockForUpdate()->first();

            $lockedKaizen->assigned_user_id = $assigneeId;
            $lockedKaizen->target_date = $targetDate;
            $lockedKaizen->save();

            // Create status history entry
            KaizenStatusHistory::create([
                'kaizen_id' => $lockedKaizen->id,
                'from_status' => KaizenStatus::APPROVED->value,
                'to_status' => KaizenStatus::APPROVED->value,
                'transition_code' => 'ASSIGN_IMPLEMENTATION',
                'actor_user_id' => $actor->id,
                'metadata' => [
                    'assigned_user_id' => $assigneeId,
                    'target_date' => $targetDate,
                ],
            ]);

            return $lockedKaizen;
        });
    }
}
