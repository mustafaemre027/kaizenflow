<?php

namespace App\Http\Controllers\Settings;

use App\Actions\ApprovalConfiguration\CreateApprovalWorkflowDraft;
use App\Actions\ApprovalConfiguration\DeactivateApprovalWorkflow;
use App\Actions\ApprovalConfiguration\PublishApprovalWorkflow;
use App\Actions\ApprovalConfiguration\SetDefaultApprovalWorkflow;
use App\Actions\ApprovalConfiguration\UpdateApprovalWorkflowDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApprovalWorkflowRequest;
use App\Http\Requests\UpdateApprovalWorkflowRequest;
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
            ],
        ]);
    }

    public function show(int $id, ShowApprovalWorkflow $query): JsonResponse
    {
        Gate::authorize('view', ApprovalWorkflow::class);

        $workflow = $query->execute($id);

        return response()->json([
            'data' => array_merge($workflow->toArray(), [
                'stages' => $workflow->stages->toArray(),
            ]),
        ]);
    }

    public function store(StoreApprovalWorkflowRequest $request, CreateApprovalWorkflowDraft $action): JsonResponse
    {
        $workflow = $action->execute(
            $request->user(),
            $request->validated('code'),
            $request->validated('name'),
            $request->validated('description'),
            $request->validated('stages')
        );

        return response()->json(['data' => $workflow], 201);
    }

    public function update(int $id, UpdateApprovalWorkflowRequest $request, UpdateApprovalWorkflowDraft $action): JsonResponse
    {
        $workflow = ApprovalWorkflow::findOrFail($id);

        $workflow = $action->execute(
            $request->user(),
            $workflow,
            $request->validated('name'),
            $request->validated('description'),
            $request->validated('stages')
        );

        return response()->json(['data' => $workflow]);
    }

    public function publish(int $id, PublishApprovalWorkflow $action): JsonResponse
    {
        Gate::authorize('publish', ApprovalWorkflow::class);

        $workflow = ApprovalWorkflow::findOrFail($id);

        $workflow = $action->execute(request()->user(), $workflow);

        return response()->json(['data' => $workflow]);
    }

    public function setDefault(int $id, SetDefaultApprovalWorkflow $action): JsonResponse
    {
        Gate::authorize('setDefault', ApprovalWorkflow::class);

        $workflow = ApprovalWorkflow::findOrFail($id);

        $workflow = $action->execute(request()->user(), $workflow);

        return response()->json(['data' => $workflow]);
    }

    public function deactivate(int $id, DeactivateApprovalWorkflow $action): JsonResponse
    {
        Gate::authorize('deactivate', ApprovalWorkflow::class);

        $workflow = ApprovalWorkflow::findOrFail($id);

        $workflow = $action->execute(request()->user(), $workflow);

        return response()->json(['data' => $workflow]);
    }
}
