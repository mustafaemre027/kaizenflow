<?php

namespace App\Services\Workflow;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CreatedKaizenHistoryQuery
{
    public function forUser(User $user): Builder
    {
        return Kaizen::query()
            ->where('creator_user_id', $user->id)
            ->with([
                'category',
                'department',
                'workflowInstance.currentStage',
            ]);
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['q'])) {
            $searchTerm = '%'.$filters['q'].'%';
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('code', 'like', $searchTerm)
                    ->orWhere('title', 'like', $searchTerm);
            });
        }

        if (! empty($filters['status']) && KaizenStatus::tryFrom($filters['status']) !== null) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('updated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('updated_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
