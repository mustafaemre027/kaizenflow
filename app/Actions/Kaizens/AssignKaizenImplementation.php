<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\AppendAuditLog;
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

            $previousAssignedId = $lockedKaizen->assigned_user_id;
            $previousTargetDate = $lockedKaizen->target_date ? $lockedKaizen->target_date->format('Y-m-d') : null;

            $lockedKaizen->assigned_user_id = $assigneeId;
            $lockedKaizen->target_date = $targetDate;
            $lockedKaizen->save();

            /** @var AppendAuditLog $auditLogger */
            $auditLogger = app(AppendAuditLog::class);
            $auditLogger->execute($actor, $lockedKaizen, 'implementation.assigned', [
                'previous_assigned_user_id' => $previousAssignedId,
                'assigned_user_id' => $assigneeId,
                'previous_target_date' => $previousTargetDate,
                'target_date' => $targetDate,
            ]);

            return $lockedKaizen;
        });
    }
}
