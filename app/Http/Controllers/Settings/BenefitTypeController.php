<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\BenefitTypes\CreateBenefitType;
use App\Actions\Settings\BenefitTypes\SetBenefitTypeStatus;
use App\Actions\Settings\BenefitTypes\UpdateBenefitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SetBenefitTypeStatusRequest;
use App\Http\Requests\Settings\StoreBenefitTypeRequest;
use App\Http\Requests\Settings\UpdateBenefitTypeRequest;
use App\Models\BenefitType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;

class BenefitTypeController extends Controller
{
    use AuthorizesRequests;

    public function create()
    {
        $this->authorize('create', BenefitType::class);

        return view('settings.benefit-types.create');
    }

    public function store(StoreBenefitTypeRequest $request, CreateBenefitType $action)
    {
        try {
            $action->execute($request->validated());

            return redirect()->route('settings.reference-data.index', ['tab' => 'benefit-types'])
                ->with('status', 'Fayda türü başarıyla oluşturuldu.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function edit(BenefitType $benefitType)
    {
        $this->authorize('update', $benefitType);

        $benefitType->loadCount('kaizenBenefits');

        return view('settings.benefit-types.edit', compact('benefitType'));
    }

    public function update(UpdateBenefitTypeRequest $request, BenefitType $benefitType, UpdateBenefitType $action)
    {
        try {
            $action->execute($benefitType, $request->validated());

            return redirect()->route('settings.reference-data.index', ['tab' => 'benefit-types'])
                ->with('status', 'Fayda türü başarıyla güncellendi.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function toggleStatus(SetBenefitTypeStatusRequest $request, BenefitType $benefitType, SetBenefitTypeStatus $action)
    {
        $this->authorize('update', $benefitType);

        $isActive = $request->validated('is_active');
        $action->execute($benefitType, $isActive);

        $statusMessage = $isActive ? 'Fayda türü aktifleştirildi.' : 'Fayda türü pasife alındı.';

        return back()->with('status', $statusMessage);
    }
}
