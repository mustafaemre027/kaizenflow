<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\KaizenStatusHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CompleteKaizenImplementation
{
    public function execute(Kaizen $kaizen, User $actor, ?string $actualResult): Kaizen
    {
        if (! $actor->can('completeImplementation', $kaizen)) {
            throw new AuthorizationException('You are not authorized to complete implementation for this Kaizen.');
        }

        if ($kaizen->status !== KaizenStatus::IN_PROGRESS) {
            throw new \Exception('Only IN_PROGRESS Kaizens can be completed.');
        }

        if (trim((string) $actualResult) === '') {
            throw new \InvalidArgumentException('Kaizen actual result is required and cannot be empty.');
        }

        return DB::transaction(function () use ($kaizen, $actor, $actualResult) {
            $lockedKaizen = Kaizen::where('id', $kaizen->id)->lockForUpdate()->first();

            if ($lockedKaizen->status !== KaizenStatus::IN_PROGRESS) {
                throw new \Exception('Only IN_PROGRESS Kaizens can be completed.');
            }

            $lockedKaizen->status = KaizenStatus::COMPLETED;
            $lockedKaizen->completed_at = now();
            $lockedKaizen->actual_result = $actualResult;
            $lockedKaizen->save();

            // Create status history entry
            KaizenStatusHistory::create([
                'kaizen_id' => $lockedKaizen->id,
                'from_status' => KaizenStatus::IN_PROGRESS->value,
                'to_status' => KaizenStatus::COMPLETED->value,
                'transition_code' => 'COMPLETE_IMPLEMENTATION',
                'actor_user_id' => $actor->id,
            ]);

            return $lockedKaizen;
        });
    }
}
