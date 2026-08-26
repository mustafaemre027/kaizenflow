<?php

namespace App\Queries;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\User;

class KaizenImplementationWorkQueueSummaryQuery
{
    public function execute(?User $actor): array
    {
        if (! $actor || ! $actor->is_active) {
            return [
                'active_count' => 0,
                'overdue_count' => 0,
                'today_count' => 0,
            ];
        }

        $terminalStatuses = array_filter(KaizenStatus::cases(), fn (KaizenStatus $s) => $s->isTerminal());
        $terminalValues = array_map(fn (KaizenStatus $s) => $s->value, $terminalStatuses);

        $today = now()->startOfDay()->format('Y-m-d');
        $todayLike = $today . '%';

        $result = Kaizen::query()
            ->where('assigned_user_id', $actor->id)
            ->whereNotIn('status', $terminalValues)
            ->selectRaw('COUNT(*) as active_count')
            ->selectRaw('SUM(CASE WHEN target_date IS NOT NULL AND target_date < ? THEN 1 ELSE 0 END) as overdue_count', [$today])
            ->selectRaw('SUM(CASE WHEN target_date IS NOT NULL AND target_date LIKE ? THEN 1 ELSE 0 END) as today_count', [$todayLike])
            ->first();

        return [
            'active_count' => (int) ($result->active_count ?? 0),
            'overdue_count' => (int) ($result->overdue_count ?? 0),
            'today_count' => (int) ($result->today_count ?? 0),
        ];
    }
}
