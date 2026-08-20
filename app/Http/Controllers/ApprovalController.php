<?php

namespace App\Http\Controllers;

use App\Services\Workflow\PendingApprovalsQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request, PendingApprovalsQuery $pendingQuery): View
    {
        $query = $pendingQuery->forUser($request->user());

        if ($request->filled('q')) {
            $searchTerm = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('code', 'like', $searchTerm)
                    ->orWhere('title', 'like', $searchTerm);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $approvals = $query->with([
            'creator',
            'department',
            'category',
            'workflowInstance.currentStage',
        ])
            ->orderBy('submitted_at', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('approvals.index', compact('approvals'));
    }
}
