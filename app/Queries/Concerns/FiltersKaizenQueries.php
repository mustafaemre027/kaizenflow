<?php

namespace App\Queries\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait FiltersKaizenQueries
{
    protected function applyKaizenFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->where('kaizens.created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('kaizens.created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (! empty($filters['department_id'])) {
            $query->where('kaizens.department_id', $filters['department_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('kaizens.category_id', $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('kaizens.status', $filters['status']);
        }
    }
}
