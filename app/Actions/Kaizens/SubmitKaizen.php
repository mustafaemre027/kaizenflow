<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenStatus;
use App\Exceptions\InvalidKaizenTransition;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\Kaizens\KaizenTransitionMap;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitKaizen
{
    public function __construct(private readonly KaizenTransitionMap $transitionMap) {}

    public function execute(User $actor, Kaizen $kaizen, ?string $reason = null): Kaizen
    {
        return DB::transaction(function () use ($actor, $kaizen, $reason) {
            $lockedKaizen = Kaizen::where('id', $kaizen->id)->lockForUpdate()->firstOrFail();

            if (! $actor->is_active) {
                throw new AuthorizationException('Inactive users cannot perform this action.');
            }

            if ($lockedKaizen->creator_user_id !== $actor->id) {
                throw new AuthorizationException('Only the creator can submit this Kaizen.');
            }

            $fromStatus = $lockedKaizen->status;
            $toStatus = KaizenStatus::SUBMITTED;

            if (! in_array($fromStatus, [KaizenStatus::DRAFT, KaizenStatus::REVISION_REQUESTED], true)) {
                throw new InvalidKaizenTransition($fromStatus, $toStatus);
            }

            if (! $this->transitionMap->canRolePerformTransition($fromStatus, $toStatus, $actor->role)) {
                throw new AuthorizationException('Your role is not authorized to submit this Kaizen.');
            }

            $transitionCode = $this->transitionMap->getTransitionCode($fromStatus, $toStatus);

            $reason = is_string($reason) ? trim($reason) : null;
            $reason = $reason === '' ? null : $reason;

            if (is_string($reason) && mb_strlen($reason) > 2000) {
                throw ValidationException::withMessages([
                    'reason' => 'Açıklama 2000 karakterden uzun olamaz.',
                ]);
            }

            $lockedKaizen->status = $toStatus;
            $lockedKaizen->submitted_at = now();
            $lockedKaizen->save();

            $lockedKaizen->statusHistories()->create([
                'actor_user_id' => $actor->id,
                'transition_code' => $transitionCode,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'reason' => $reason,
                'metadata' => null,
            ]);

            return $lockedKaizen->refresh();
        });
    }
}
