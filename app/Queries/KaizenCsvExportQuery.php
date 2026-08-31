<?php

namespace App\Queries;

use App\Models\User;
use App\Queries\Concerns\FiltersKaizenQueries;
use App\Services\Kaizens\VisibleKaizensQuery;
use Illuminate\Support\LazyCollection;

class KaizenCsvExportQuery
{
    use FiltersKaizenQueries;

    public function __construct(
        private readonly VisibleKaizensQuery $visibleKaizens
    ) {}

    public function execute(User $actor, array $filters): LazyCollection
    {
        $query = $this->visibleKaizens->forUser($actor);

        // We explicitly specify 'kaizens.*' initially or apply joins directly.
        // Since we are building a flattened cursor, we will select explicitly.

        $this->applyKaizenFilters($query, $filters);

        return $query
            ->leftJoin('departments', 'kaizens.department_id', '=', 'departments.id')
            ->leftJoin('categories', 'kaizens.category_id', '=', 'categories.id')
            ->leftJoin('kaizen_benefits', 'kaizens.id', '=', 'kaizen_benefits.kaizen_id')
            ->leftJoin('benefit_types', 'kaizen_benefits.benefit_type_id', '=', 'benefit_types.id')
            ->select([
                'kaizens.code',
                'kaizens.title',
                'kaizens.status',
                'departments.name as department_name',
                'categories.name as category_name',
                'kaizens.priority',
                'kaizens.target_date',
                'kaizens.created_at',
                'benefit_types.name as benefit_type_name',
                'kaizen_benefits.expected_value',
                'kaizen_benefits.realized_value',
                'benefit_types.unit_label as benefit_unit_label',
            ])
            ->orderByDesc('kaizens.created_at')
            ->orderByDesc('kaizens.id')
            ->orderBy('benefit_types.name')
            ->cursor();
    }
}
