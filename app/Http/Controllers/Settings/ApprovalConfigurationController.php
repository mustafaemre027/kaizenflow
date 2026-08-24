<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ApprovalWorkflow;
use App\Queries\ApprovalConfiguration\ListApprovalWorkflows;
use App\Queries\ApprovalConfiguration\ShowApprovalWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ApprovalConfigurationController extends Controller
{
    public function index(ListApprovalWorkflows $query): JsonResponse
    {
        Gate::authorize('viewAny', ApprovalWorkflow::class);

        $workflows = $query->execute();

        return response()->json([
            'data' => $workflows->items(),
            'meta' => [
                'current_page' => $workflows->currentPage(),
                'last_page' => $workflows->lastPage(),
                'total' => $workflows->total(),
            ]
        ]);
    }

    public function show(int $id, ShowApprovalWorkflow $query): JsonResponse
    {
        Gate::authorize('view', ApprovalWorkflow::class);

        $workflow = $query->execute($id);

        return response()->json([
            'data' => array_merge($workflow->toArray(), [
                'stages' => $workflow->stages->toArray()
            ])
        ]);
    }
}

