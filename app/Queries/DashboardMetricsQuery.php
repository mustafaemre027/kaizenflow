<?php

namespace App\Queries;

use App\Enums\KaizenStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\KaizenBenefit;
use App\Models\User;
use App\Services\Kaizens\VisibleKaizensQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardMetricsQuery
{
    public function __construct(
        private readonly VisibleKaizensQuery $visibleKaizens
    ) {}

    public function execute(User $actor, array $filters): array
    {
        // 1. Base Scoped Query
        $baseQuery = $this->visibleKaizens->forUser($actor);
        $this->applyFilters($baseQuery, $filters);

        // 2. Metrics Object
        return [
            'total_kaizens' => $this->getTotal($baseQuery),
            'in_process_kaizens' => $this->getInProcessCount($baseQuery),
            'completed_kaizens' => $this->getCompletedCount($baseQuery),
            'overdue_kaizens' => $this->getOverdueCount($baseQuery),
            'status_distribution' => $this->getStatusDistribution($baseQuery),
            'department_breakdown' => $this->getDepartmentBreakdown($baseQuery),
            'category_breakdown' => $this->getCategoryBreakdown($baseQuery),
            'monthly_trend' => $this->getMonthlyTrend($baseQuery),
            'structured_benefits' => $this->getStructuredBenefits($baseQuery),
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    private function getTotal(Builder $baseQuery): int
    {
        return (clone $baseQuery)->count();
    }

    private function getInProcessCount(Builder $baseQuery): int
    {
        $terminalStatuses = array_map(fn ($case) => $case->value, array_filter(
            KaizenStatus::cases(),
            fn ($case) => $case->isTerminal()
        ));

        return (clone $baseQuery)
            ->whereNotIn('status', array_merge($terminalStatuses, [KaizenStatus::DRAFT->value]))
            ->count();
    }

    private function getCompletedCount(Builder $baseQuery): int
    {
        return (clone $baseQuery)
            ->where('status', KaizenStatus::COMPLETED->value)
            ->count();
    }

    private function getOverdueCount(Builder $baseQuery): int
    {
        $terminalStatuses = array_map(fn ($case) => $case->value, array_filter(
            KaizenStatus::cases(),
            fn ($case) => $case->isTerminal()
        ));

        return (clone $baseQuery)
            ->whereNotIn('status', array_merge($terminalStatuses, [KaizenStatus::DRAFT->value]))
            ->where('target_date', '<', Carbon::today())
            ->count();
    }

    private function getStatusDistribution(Builder $baseQuery): array
    {
        // One DB query to get counts grouped by status
        $counts = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray();

        $total = array_sum($counts);
        $distribution = [];

        foreach (KaizenStatus::cases() as $status) {
            $count = $counts[$status->value] ?? 0;
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;

            $distribution[] = [
                'status' => $status,
                'label' => $status->label(),
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $distribution;
    }

    private function getDepartmentBreakdown(Builder $baseQuery): array
    {
        $breakdown = (clone $baseQuery)
            ->select('department_id', DB::raw('count(*) as count'))
            ->groupBy('department_id')
            ->orderByDesc('count')
            ->get();

        if ($breakdown->isEmpty()) {
            return [];
        }

        $departmentIds = $breakdown->pluck('department_id')->filter()->toArray();
        $departments = Department::whereIn('id', $departmentIds)->get()->keyBy('id');

        $result = [];
        foreach ($breakdown as $row) {
            $name = $row->department_id && $departments->has($row->department_id)
                ? $departments->get($row->department_id)->name
                : 'Departmansız';

            $result[] = [
                'department_id' => $row->department_id,
                'name' => $name,
                'count' => $row->count,
            ];
        }

        // Sort explicitly by count desc, then name asc
        usort($result, function ($a, $b) {
            if ($a['count'] === $b['count']) {
                return strcmp($a['name'], $b['name']);
            }

            return $b['count'] <=> $a['count'];
        });

        return $result;
    }

    private function getCategoryBreakdown(Builder $baseQuery): array
    {
        $breakdown = (clone $baseQuery)
            ->select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->get();

        if ($breakdown->isEmpty()) {
            return [];
        }

        $categoryIds = $breakdown->pluck('category_id')->filter()->toArray();
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

        $result = [];
        foreach ($breakdown as $row) {
            $name = $row->category_id && $categories->has($row->category_id)
                ? $categories->get($row->category_id)->name
                : 'Kategorisiz';

            $result[] = [
                'category_id' => $row->category_id,
                'name' => $name,
                'count' => $row->count,
            ];
        }

        usort($result, function ($a, $b) {
            if ($a['count'] === $b['count']) {
                return strcmp($a['name'], $b['name']);
            }

            return $b['count'] <=> $a['count'];
        });

        return $result;
    }

    private function getMonthlyTrend(Builder $baseQuery): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $startMonth = Carbon::now()->subMonths(11)->startOfMonth(); // Last 12 months including this month

        $trendData = (clone $baseQuery)
            ->where('created_at', '>=', $startMonth)
            ->select(DB::raw("{$dateExpr} as month_key"), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('month_key'))
            ->orderBy('month_key')
            ->pluck('count', 'month_key')
            ->toArray();

        $result = [];
        // Fill 12 months
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths(11 - $i);
            $key = $date->format('Y-m');
            $result[] = [
                'month_key' => $key,
                'label' => $date->translatedFormat('M Y'),
                'count' => $trendData[$key] ?? 0,
            ];
        }

        return $result;
    }

    private function getStructuredBenefits(Builder $baseQuery): array
    {
        // Subquery approach to isolate strictly the visible kaizens
        $subquery = clone $baseQuery;

        $benefits = KaizenBenefit::query()
            ->joinSub($subquery, 'visible_kaizens', function ($join) {
                $join->on('kaizen_benefits.kaizen_id', '=', 'visible_kaizens.id');
            })
            ->select(
                'kaizen_benefits.benefit_type_id',
                DB::raw('SUM(kaizen_benefits.expected_value) as expected_total'),
                DB::raw('SUM(kaizen_benefits.realized_value) as realized_total'),
                DB::raw('COUNT(kaizen_benefits.id) as kaizen_count')
            )
            ->groupBy('kaizen_benefits.benefit_type_id')
            ->with('benefitType')
            ->get();

        $result = [];
        foreach ($benefits as $benefit) {
            if (! $benefit->benefitType) {
                continue;
            }

            $result[] = [
                'benefit_type_id' => $benefit->benefit_type_id,
                'name' => $benefit->benefitType->name,
                'is_active' => $benefit->benefitType->is_active,
                'unit_label' => $benefit->benefitType->unit_label ?? '',
                'expected_total' => $benefit->expected_total,
                'realized_total' => $benefit->realized_total,
                'kaizen_count' => $benefit->kaizen_count,
            ];
        }

        // Sort by name
        usort($result, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }
}
