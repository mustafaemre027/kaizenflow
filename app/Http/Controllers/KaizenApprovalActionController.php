<?php

namespace App\Http\Controllers;

use App\Actions\Workflow\ProgressKaizenWorkflow;
use App\Enums\WorkflowAction;
use App\Http\Requests\Workflow\ProgressKaizenWorkflowRequest;
use App\Models\Kaizen;
use Illuminate\Support\Facades\Gate;

class KaizenApprovalActionController extends Controller
{
    public function approve(Kaizen $kaizen, ProgressKaizenWorkflowRequest $request, ProgressKaizenWorkflow $action)
    {
        Gate::authorize('reviewOnWorkflow', $kaizen);

        try {
            $isFinal = $kaizen->workflowInstance?->currentStage?->is_final;

            $action->execute(
                $kaizen,
                $request->user(),
                WorkflowAction::APPROVE,
                $request->input('comment')
            );

            $message = $isFinal
                ? 'Kaizen onay süreci başarıyla tamamlandı.'
                : 'Kaizen onaylandı ve bir sonraki aşamaya ilerletildi.';

            return redirect()->route('approvals.index')->with('success', $message);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', 'Bu işlem artık gerçekleştirilemiyor. Onay süreci değişmiş olabilir.');
        }
    }

    public function requestRevision(Kaizen $kaizen, ProgressKaizenWorkflowRequest $request, ProgressKaizenWorkflow $action)
    {
        Gate::authorize('reviewOnWorkflow', $kaizen);

        try {
            $action->execute(
                $kaizen,
                $request->user(),
                WorkflowAction::REQUEST_REVISION,
                $request->input('comment')
            );

            return redirect()->route('approvals.index')->with('success', 'Kaizen revizyon için sahibine geri gönderildi.');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', 'Bu işlem artık gerçekleştirilemiyor. Onay süreci değişmiş olabilir.');
        }
    }

    public function reject(Kaizen $kaizen, ProgressKaizenWorkflowRequest $request, ProgressKaizenWorkflow $action)
    {
        Gate::authorize('reviewOnWorkflow', $kaizen);

        try {
            $action->execute(
                $kaizen,
                $request->user(),
                WorkflowAction::REJECT,
                $request->input('comment')
            );

            return redirect()->route('approvals.index')->with('success', 'Kaizen reddedildi ve onay süreci kapatıldı.');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', 'Bu işlem artık gerçekleştirilemiyor. Onay süreci değişmiş olabilir.');
        }
    }
}
