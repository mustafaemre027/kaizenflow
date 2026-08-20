<?php

namespace App\Services\Workflow;

use App\Enums\WorkflowAction;
use App\Models\KaizenWorkflowTransition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ReviewedKaizenHistoryQuery
{
    /**
     * Returns a scoped Builder for all workflow transitions where
     * the given user was the actor and the action is a review decision.
     * START and RESUBMIT are excluded — those are creator actions.
     */
    public function forUser(User $user): Builder
    {
        $reviewActions = array_map(
            fn (WorkflowAction $a) => $a->value,
            WorkflowAction::reviewActions()
        );

        return KaizenWorkflowTransition::query()
            ->where('actor_user_id', $user->id)
            ->whereIn('action', $reviewActions)
            ->with([
                'kaizen.category',
                'kaizen.department',
                'fromStage',
            ]);
    }

    /**
     * Applies optional filters to the base query.
     * Cannot widen scope beyond actor_user_id = $user.
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['q'])) {
            $searchTerm = '%'.$filters['q'].'%';
            $query->whereHas('kaizen', function (Builder $kq) use ($searchTerm) {
                $kq->where('code', 'like', $searchTerm)
                    ->orWhere('title', 'like', $searchTerm);
            });
        }

        if (! empty($filters['action']) && WorkflowAction::tryFrom($filters['action']) !== null) {
            $action = WorkflowAction::from($filters['action']);
            if (in_array($action, WorkflowAction::reviewActions(), true)) {
                $query->where('action', $filters['action']);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('kaizen_workflow_transitions.created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('kaizen_workflow_transitions.created_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
