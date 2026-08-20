<?php

namespace App\Http\Controllers;

use App\Actions\Kaizens\CreateKaizenDraftWithEvidence;
use App\Actions\Kaizens\SubmitKaizen;
use App\Actions\Kaizens\UpdateKaizenDraftWithEvidence;
use App\Enums\KaizenAttachmentContext;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Exceptions\Workflow\InvalidApprovalWorkflowConfiguration;
use App\Http\Requests\Kaizens\IndexKaizenRequest;
use App\Http\Requests\Kaizens\StoreKaizenRequest;
use App\Http\Requests\Kaizens\SubmitKaizenRequest;
use App\Http\Requests\Kaizens\UpdateKaizenDraftRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\Kaizens\VisibleKaizensQuery;
use App\Services\Workflow\KaizenWorkflowTimelinePresenter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class KaizenController extends Controller
{
    public function index(IndexKaizenRequest $request, VisibleKaizensQuery $visibleKaizens)
    {
        Gate::authorize('viewAny', Kaizen::class);

        $validated = $request->validated();
        $user = $request->user();

        $baseQuery = $visibleKaizens->forUser($user);
        $query = clone $baseQuery;

        if (! empty($validated['q'])) {
            $searchTerm = '%'.$validated['q'].'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('code', 'LIKE', $searchTerm)
                    ->orWhere('title', 'LIKE', $searchTerm)
                    ->orWhere('current_situation', 'LIKE', $searchTerm)
                    ->orWhere('proposed_situation', 'LIKE', $searchTerm);
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (! empty($validated['department_id'])) {
            $query->where('department_id', $validated['department_id']);
        }

        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';

        $query->orderBy($sort, $direction);

        $kaizens = $query->with(['category', 'department', 'creator', 'assignedUser'])
            ->paginate(15)
            ->withQueryString();

        $statuses = KaizenStatus::cases();
        $categories = Category::active()->orderBy('name')->get();

        if ($user->role === UserRole::MANAGER) {
            $departments = Department::where('id', $user->department_id)->active()->get();
        } elseif (in_array($user->role, [UserRole::OPEX_SPECIALIST, UserRole::ADMIN], true)) {
            $departments = Department::active()->orderBy('name')->get();
        } else {
            $departmentIds = (clone $baseQuery)->select('department_id')->distinct()->pluck('department_id');
            $departments = Department::whereIn('id', $departmentIds)->active()->orderBy('name')->get();
        }

        return view('kaizens.index', compact('kaizens', 'statuses', 'categories', 'departments'));
    }

    public function create()
    {
        Gate::authorize('create', Kaizen::class);

        $categories = Category::active()->orderBy('name')->get();

        return view('kaizens.create', compact('categories'));
    }

    public function edit(Kaizen $kaizen)
    {
        Gate::authorize('update', $kaizen);

        $categories = Category::active()->orderBy('name')->get();

        if ($kaizen->category_id && ! $categories->contains('id', $kaizen->category_id)) {
            $kaizen->load('category');
            if ($kaizen->category) {
                $categories->push($kaizen->category);
            }
        }

        $kaizen->load(['attachments' => function ($query) {
            $query->orderBy('context')->orderBy('sort_order');
        }]);

        $currentSituationAttachments = $kaizen->attachments->where('context', KaizenAttachmentContext::CURRENT_SITUATION->value);
        $proposedSituationAttachments = $kaizen->attachments->where('context', KaizenAttachmentContext::PROPOSED_SITUATION->value);

        return view('kaizens.edit', compact(
            'kaizen',
            'categories',
            'currentSituationAttachments',
            'proposedSituationAttachments'
        ));
    }

    public function show(Kaizen $kaizen, KaizenWorkflowTimelinePresenter $presenter)
    {
        Gate::authorize('view', $kaizen);

        $kaizen->load([
            'creator',
            'assignedUser',
            'department',
            'category',
            'attachments' => function ($query) {
                $query->orderBy('context')->orderBy('sort_order');
            },
            'workflowInstance.workflow.stages',
        ]);

        $currentSituationAttachments = $kaizen->attachments->where('context', KaizenAttachmentContext::CURRENT_SITUATION->value);
        $proposedSituationAttachments = $kaizen->attachments->where('context', KaizenAttachmentContext::PROPOSED_SITUATION->value);

        $workflowTimeline = $presenter->present($kaizen);

        $implementationCandidates = [];
        if ($kaizen->status === KaizenStatus::APPROVED && ! $kaizen->assigned_user_id) {
            if (request()->user()->can('assignImplementation', $kaizen)) {
                $implementationCandidates = User::where('is_active', true)
                    ->where('department_id', $kaizen->department_id)
                    ->orderBy('name')
                    ->select('id', 'name')
                    ->get();
            }
        }

        return view('kaizens.show', compact(
            'kaizen',
            'workflowTimeline',
            'currentSituationAttachments',
            'proposedSituationAttachments',
            'implementationCandidates'
        ));
    }

    public function store(StoreKaizenRequest $request, CreateKaizenDraftWithEvidence $createAction)
    {
        $validated = $request->validated();
        $user = $request->user();
        $category = Category::find($validated['category_id']);

        $currentSituationImages = $request->file('current_situation_images', []);
        $proposedSituationImages = $request->file('proposed_situation_images', []);

        $kaizen = $createAction->execute(
            $user,
            $category,
            $validated,
            $currentSituationImages,
            $proposedSituationImages
        );

        if ($request->expectsJson()) {
            return response()->json(
                $kaizen->only([
                    'id',
                    'code',
                    'title',
                    'status',
                    'category_id',
                    'department_id',
                    'priority',
                    'target_date',
                ]),
                201
            );
        }

        return redirect()->route('kaizens.show', $kaizen)->with('success', 'Kaizen taslağı başarıyla oluşturuldu.');
    }

    public function update(UpdateKaizenDraftRequest $request, Kaizen $kaizen, UpdateKaizenDraftWithEvidence $updateAction)
    {
        $validated = $request->validated();
        $user = $request->user();

        $updatedKaizen = $updateAction->execute($user, $kaizen, $validated);

        if ($request->expectsJson()) {
            return response()->json(
                $updatedKaizen->only([
                    'id',
                    'code',
                    'title',
                    'status',
                    'category_id',
                    'department_id',
                    'priority',
                    'target_date',
                ]),
                200
            );
        }

        return redirect()->route('kaizens.show', $updatedKaizen)->with('success', 'Kaizen taslağı başarıyla güncellendi.');
    }

    public function submit(SubmitKaizenRequest $request, Kaizen $kaizen, SubmitKaizen $submitAction)
    {
        $validated = $request->validated();
        $user = $request->user();

        $reason = $validated['reason'] ?? null;

        try {
            $submittedKaizen = $submitAction->execute($user, $kaizen, $reason);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Kaizen başarıyla gönderildi.',
                    'kaizen' => $submittedKaizen->only([
                        'id',
                        'code',
                        'status',
                        'submitted_at',
                    ]),
                ], 200);
            }

            return back()->with('success', 'Kaizen başarıyla gönderildi.');
        } catch (InvalidApprovalWorkflowConfiguration $e) {
            Log::error('Workflow configuration failure during Kaizen submit.', [
                'kaizen_id' => $kaizen->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Onay süreci yapılandırması tamamlanmamış. Lütfen sistem yöneticisine başvurun.',
                ], 422);
            }

            return back()->with('error', 'Kaizen şu anda onaya gönderilemiyor. Onay süreci yapılandırması tamamlanmamış. Lütfen sistem yöneticisine başvurun.');
        }
    }
}
