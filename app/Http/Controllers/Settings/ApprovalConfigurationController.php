<?php

namespace App\Http\Controllers\Settings;

use App\Actions\ApprovalConfiguration\CreateApprovalWorkflowDraft;
use App\Actions\ApprovalConfiguration\DeactivateApprovalWorkflow;
use App\Actions\ApprovalConfiguration\PublishApprovalWorkflow;
use App\Actions\ApprovalConfiguration\SetDefaultApprovalWorkflow;
use App\Actions\ApprovalConfiguration\UpdateApprovalWorkflowDraft;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApprovalWorkflowRequest;
use App\Http\Requests\UpdateApprovalWorkflowRequest;
use App\Models\ApprovalWorkflow;
use App\Queries\ApprovalConfiguration\ListApprovalWorkflows;
use App\Queries\ApprovalConfiguration\ShowApprovalWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApprovalConfigurationController extends Controller
{
    public function index(Request $request, ListApprovalWorkflows $query): JsonResponse|View
    {
        Gate::authorize('viewAny', ApprovalWorkflow::class);

        $workflows = $query->execute();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $workflows->items(),
                'meta' => [
                    'current_page' => $workflows->currentPage(),
                    'last_page' => $workflows->lastPage(),
                    'total' => $workflows->total(),
                ],
            ]);
        }

        return view('settings.approval-configurations.index', compact('workflows'));
    }

    public function create(): View
    {
        Gate::authorize('create', ApprovalWorkflow::class);

        return view('settings.approval-configurations.create');
    }

    public function show(Request $request, int $id, ShowApprovalWorkflow $query): JsonResponse|View
    {
        Gate::authorize('view', ApprovalWorkflow::class);

        $workflow = $query->execute($id);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => array_merge($workflow->toArray(), [
                    'stages' => $workflow->stages->toArray(),
                ]),
            ]);
        }

        return view('settings.approval-configurations.show', compact('workflow'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        Gate::authorize('update', ApprovalWorkflow::class);
        $workflow = ApprovalWorkflow::findOrFail($id);

        if ($workflow->published_at !== null) {
            return redirect()->route('settings.approval-configurations.show', $id)
                ->with('error', 'Yalnızca taslak durumundaki yapılandırmalar düzenlenebilir.');
        }

        return view('settings.approval-configurations.edit', compact('workflow'));
    }

    public function store(StoreApprovalWorkflowRequest $request, CreateApprovalWorkflowDraft $action): JsonResponse|RedirectResponse
    {
        try {
            $workflow = $action->execute(
                $request->user(),
                $request->validated('code'),
                $request->validated('name'),
                $request->validated('description'),
                $request->validated('stages') ?? []
            );

            if ($request->wantsJson()) {
                return response()->json(['data' => $workflow], 201);
            }

            return redirect()->route('settings.approval-configurations.show', $workflow->id)
                ->with('success', 'Onay yapılandırması taslağı oluşturuldu.');
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                throw $e;
            }
            return back()->withInput()->with('error', 'İşlem kurallara uymuyor.');
        }
    }

    public function update(int $id, UpdateApprovalWorkflowRequest $request, UpdateApprovalWorkflowDraft $action): JsonResponse|RedirectResponse
    {
        try {
            $workflow = ApprovalWorkflow::findOrFail($id);

            $workflow = $action->execute(
                $request->user(),
                $workflow,
                $request->validated('name'),
                $request->validated('description'),
                $request->validated('stages') ?? []
            );

            if ($request->wantsJson()) {
                return response()->json(['data' => $workflow]);
            }

            return redirect()->route('settings.approval-configurations.show', $workflow->id)
                ->with('success', 'Onay yapılandırması başarıyla güncellendi.');
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                throw $e;
            }
            return back()->withInput()->with('error', 'İşlem kurallara uymuyor.');
        }
    }

    public function publish(Request $request, int $id, PublishApprovalWorkflow $action): JsonResponse|RedirectResponse
    {
        Gate::authorize('publish', ApprovalWorkflow::class);
        
        try {
            $workflow = ApprovalWorkflow::findOrFail($id);
            $workflow = $action->execute($request->user(), $workflow);

            if ($request->wantsJson()) {
                return response()->json(['data' => $workflow]);
            }

            return redirect()->route('settings.approval-configurations.show', $workflow->id)
                ->with('success', 'Onay yapılandırması başarıyla yayınlandı.');
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                throw $e;
            }
            return back()->with('error', 'İşlem kurallara uymuyor.');
        }
    }

    public function setDefault(Request $request, int $id, SetDefaultApprovalWorkflow $action): JsonResponse|RedirectResponse
    {
        Gate::authorize('setDefault', ApprovalWorkflow::class);
        
        try {
            $workflow = ApprovalWorkflow::findOrFail($id);
            $workflow = $action->execute($request->user(), $workflow);

            if ($request->wantsJson()) {
                return response()->json(['data' => $workflow]);
            }

            return redirect()->route('settings.approval-configurations.show', $workflow->id)
                ->with('success', 'Onay yapılandırması varsayılan olarak ayarlandı.');
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                throw $e;
            }
            return back()->with('error', 'İşlem kurallara uymuyor.');
        }
    }

    public function deactivate(Request $request, int $id, DeactivateApprovalWorkflow $action): JsonResponse|RedirectResponse
    {
        Gate::authorize('deactivate', ApprovalWorkflow::class);
        
        try {
            $workflow = ApprovalWorkflow::findOrFail($id);
            $workflow = $action->execute($request->user(), $workflow);

            if ($request->wantsJson()) {
                return response()->json(['data' => $workflow]);
            }

            return redirect()->route('settings.approval-configurations.index')
                ->with('success', 'Onay yapılandırması başarıyla pasifleştirildi.');
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                throw $e;
            }
            return back()->with('error', 'İşlem kurallara uymuyor.');
        }
    }
}
