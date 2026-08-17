<?php

namespace App\Http\Controllers;

use App\Actions\Kaizens\CreateKaizenDraft;
use App\Actions\Kaizens\SubmitKaizen;
use App\Actions\Kaizens\UpdateKaizenDraft;
use App\Http\Requests\Kaizens\StoreKaizenRequest;
use App\Http\Requests\Kaizens\SubmitKaizenRequest;
use App\Http\Requests\Kaizens\UpdateKaizenDraftRequest;
use App\Models\Category;
use App\Models\Kaizen;

class KaizenController extends Controller
{
    public function store(StoreKaizenRequest $request, CreateKaizenDraft $createAction)
    {
        $validated = $request->validated();
        $user = $request->user();
        $category = Category::find($validated['category_id']);

        $kaizen = $createAction->execute($user, $category, $validated);

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

        return back()->with('success', 'Kaizen taslağı başarıyla oluşturuldu.');
    }

    public function update(UpdateKaizenDraftRequest $request, Kaizen $kaizen, UpdateKaizenDraft $updateAction)
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

        return back()->with('success', 'Kaizen taslağı başarıyla güncellendi.');
    }

    public function submit(SubmitKaizenRequest $request, Kaizen $kaizen, SubmitKaizen $submitAction)
    {
        $validated = $request->validated();
        $user = $request->user();

        $reason = $validated['reason'] ?? null;

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
    }
}
