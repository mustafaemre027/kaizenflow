<?php

namespace App\Http\Controllers;

use App\Models\ApprovalGroupMember;
use App\Models\Category;
use App\Services\Workflow\CreatedKaizenHistoryQuery;
use App\Services\Workflow\ReviewedKaizenHistoryQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(
        Request $request,
        CreatedKaizenHistoryQuery $createdQuery,
        ReviewedKaizenHistoryQuery $reviewedQuery
    ): View {
        $user = $request->user();
        $activeTab = $request->input('tab', 'created');

        $filters = $request->only(['q', 'status', 'category_id', 'action', 'date_from', 'date_to']);

        $createdKaizens = null;
        $reviewedTransitions = null;

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

        if ($activeTab === 'reviewed' && ! $canAccessReviewedHistory) {
            $activeTab = 'created';
        }

        if ($activeTab === 'reviewed') {
            $query = $reviewedQuery->forUser($user);
            $query = $reviewedQuery->applyFilters($query, $filters);
            $reviewedTransitions = $query
                ->orderBy('kaizen_workflow_transitions.created_at', 'desc')
                ->orderBy('kaizen_workflow_transitions.id', 'desc')
                ->paginate(15)
                ->withQueryString();
        } else {
            $activeTab = 'created';
            $query = $createdQuery->forUser($user);
            $query = $createdQuery->applyFilters($query, $filters);
            $createdKaizens = $query
                ->orderBy('kaizens.updated_at', 'desc')
                ->orderBy('kaizens.id', 'desc')
                ->paginate(15)
                ->withQueryString();
        }

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('history.index', compact(
            'activeTab',
            'createdKaizens',
            'reviewedTransitions',
            'categories',
            'filters',
            'canAccessReviewedHistory'
        ));
    }
}
