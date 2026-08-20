<?php

namespace App\Http\Controllers;

use App\Models\ApprovalGroupMember;
use App\Services\Workflow\ReviewedKaizenHistoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(
        Request $request,
        ReviewedKaizenHistoryQuery $reviewedQuery
    ): View|RedirectResponse {
        $user = $request->user();

        $filters = $request->only(['q', 'action', 'date_from', 'date_to']);

        $hasActiveMembership = ApprovalGroupMember::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('group', function ($q) {
                $q->where('is_active', true);
            })
            ->exists();

        $hasHistoricalReviews = false;
        if (! $hasActiveMembership) {
            $hasHistoricalReviews = $reviewedQuery->forUser($user)->exists();
        }

        $canAccessReviewedHistory = $hasActiveMembership || $hasHistoricalReviews;

        if (! $canAccessReviewedHistory) {
            return redirect()->route('kaizens.index');
        }

        $query = $reviewedQuery->forUser($user);
        $query = $reviewedQuery->applyFilters($query, $filters);

        $reviewedTransitions = $query
            ->orderBy('kaizen_workflow_transitions.created_at', 'desc')
            ->orderBy('kaizen_workflow_transitions.id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('history.index', compact(
            'reviewedTransitions',
            'filters'
        ));
    }
}
