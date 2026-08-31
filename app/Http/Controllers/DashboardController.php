<?php

namespace App\Http\Controllers;

use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Http\Requests\DashboardIndexRequest;
use App\Models\Category;
use App\Models\Department;
use App\Queries\DashboardMetricsQuery;
use App\Services\UserCapabilityResolver;

class DashboardController extends Controller
{
    public function __construct(
        private readonly UserCapabilityResolver $capabilityResolver,
        private readonly DashboardMetricsQuery $metricsQuery
    ) {}

    public function index(DashboardIndexRequest $request)
    {
        $user = $request->user();

        // 1. Strict authorization (capability-based, no roles)
        if (! $this->capabilityResolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW)) {
            abort(403, 'Yönetim dashboarduna erişim yetkiniz bulunmamaktadır.');
        }

        // 2. Extract validated filters
        $filters = $request->validated();

        // 3. Execute metrics query (actor-scoped)
        $metrics = $this->metricsQuery->execute($user, $filters);

        // 4. Reference data for filters
        // Only active reference data for new selections, plus any historically requested items
        $categories = Category::active()->orderBy('name')->get();
        if (! empty($filters['category_id']) && ! $categories->contains('id', $filters['category_id'])) {
            $inactiveCategory = Category::find($filters['category_id']);
            if ($inactiveCategory) {
                $categories->push($inactiveCategory);
            }
        }

        $departments = Department::active()->orderBy('name')->get();
        if (! empty($filters['department_id']) && ! $departments->contains('id', $filters['department_id'])) {
            $inactiveDepartment = Department::find($filters['department_id']);
            if ($inactiveDepartment) {
                $departments->push($inactiveDepartment);
            }
        }

        $statuses = KaizenStatus::cases();

        return view('dashboard.index', compact('metrics', 'categories', 'departments', 'statuses'));
    }
}
