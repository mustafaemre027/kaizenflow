<?php

namespace App\Queries;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KaizenImplementationWorkQueueQuery
{
    public function execute(User $actor, int $perPage = 15): LengthAwarePaginator
    {
        if (! $actor->is_active) {
            return Kaizen::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        $terminalStatuses = array_filter(KaizenStatus::cases(), fn (KaizenStatus $s) => $s->isTerminal());
        $terminalValues = array_map(fn (KaizenStatus $s) => $s->value, $terminalStatuses);

        $today = now()->startOfDay()->format('Y-m-d');

        $query = Kaizen::query()
            ->select('kaizens.*')
            ->selectRaw('CASE WHEN target_date IS NOT NULL AND target_date < ? THEN 1 ELSE 0 END as is_overdue', [$today])
            ->where('assigned_user_id', $actor->id)
            ->whereNotIn('status', $terminalValues)
            ->with(['creator', 'assignedUser', 'department', 'category']);

        $query->orderByDesc('is_overdue')
            ->orderByRaw('target_date IS NULL ASC')
            ->orderBy('target_date', 'asc')
            ->orderBy('id', 'asc');

        return $query->paginate($perPage);
    }
}
