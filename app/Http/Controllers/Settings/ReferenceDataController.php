<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\IndexReferenceDataRequest;
use App\Models\Category;
use App\Models\Department;

class ReferenceDataController extends Controller
{
    public function index(IndexReferenceDataRequest $request)
    {
        $validated = $request->validated();

        $categoryQuery = Category::withCount('kaizens');

        if (! empty($validated['category_q'])) {
            $q = '%'.$validated['category_q'].'%';
            $categoryQuery->where(function ($query) use ($q) {
                $query->where('name', 'like', $q)
                    ->orWhere('code', 'like', $q)
                    ->orWhere('description', 'like', $q);
            });
        }

        if (! empty($validated['category_status'])) {
            $categoryQuery->where('is_active', $validated['category_status'] === 'active');
        }

        $categories = $categoryQuery
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'category_page')
            ->withQueryString();

        $departmentQuery = Department::withCount([
            'kaizens',
            'users',
            'users as active_users_count' => function ($query) {
                $query->where('is_active', true);
            },
        ]);

        if (! empty($validated['department_q'])) {
            $q = '%'.$validated['department_q'].'%';
            $departmentQuery->where(function ($query) use ($q) {
                $query->where('name', 'like', $q)
                    ->orWhere('code', 'like', $q)
                    ->orWhere('description', 'like', $q);
            });
        }

        if (! empty($validated['department_status'])) {
            $departmentQuery->where('is_active', $validated['department_status'] === 'active');
        }

        $departments = $departmentQuery
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'department_page')
            ->withQueryString();

        $benefitTypeQuery = \App\Models\BenefitType::withCount('kaizenBenefits');
        
        if (! empty($validated['benefit_type_q'])) {
            $q = '%'.$validated['benefit_type_q'].'%';
            $benefitTypeQuery->where(function ($query) use ($q) {
                $query->where('name', 'like', $q)
                    ->orWhere('unit_label', 'like', $q);
            });
        }
        
        if (! empty($validated['benefit_type_status'])) {
            $benefitTypeQuery->where('is_active', $validated['benefit_type_status'] === 'active');
        }
        
        $benefitTypes = $benefitTypeQuery
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'benefit_type_page')
            ->withQueryString();

        return view('settings.reference-data.index', compact('categories', 'departments', 'benefitTypes'));
    }
}
