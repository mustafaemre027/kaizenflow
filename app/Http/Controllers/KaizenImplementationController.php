<?php

namespace App\Http\Controllers;

use App\Actions\Kaizens\AssignKaizenImplementation;
use App\Actions\Kaizens\CompleteKaizenImplementation;
use App\Actions\Kaizens\StartKaizenImplementation;
use App\Http\Requests\Kaizens\AssignKaizenImplementationRequest;
use App\Http\Requests\Kaizens\CompleteKaizenImplementationRequest;
use App\Models\Kaizen;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class KaizenImplementationController extends Controller
{
    use AuthorizesRequests;

    public function assign(AssignKaizenImplementationRequest $request, Kaizen $kaizen, AssignKaizenImplementation $action)
    {
        $this->authorize('assignImplementation', $kaizen);

        $action->execute(
            $kaizen,
            $request->user(),
            $request->validated('assigned_user_id'),
            $request->validated('target_date')
        );

        return redirect()->route('kaizens.show', $kaizen)
            ->with('success', 'Uygulama sorumlusu ve hedef tarih kaydedildi.');
    }

    public function start(Kaizen $kaizen, StartKaizenImplementation $action)
    {
        $this->authorize('startImplementation', $kaizen);

        try {
            $action->execute($kaizen, request()->user());

            return redirect()->route('kaizens.show', $kaizen)
                ->with('success', 'Kaizen uygulama süreci başlatıldı.');
        } catch (\Exception $e) {
            return redirect()->route('kaizens.show', $kaizen)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function complete(CompleteKaizenImplementationRequest $request, Kaizen $kaizen, CompleteKaizenImplementation $action)
    {
        $this->authorize('completeImplementation', $kaizen);

        $action->execute(
            $kaizen,
            $request->user(),
            $request->validated('actual_result')
        );

        return redirect()->route('kaizens.show', $kaizen)
            ->with('success', 'Kaizen uygulama süreci tamamlandı.');
    }
}
