<?php

namespace App\Queries\ApprovalConfiguration;

use App\Models\ApprovalWorkflow;
use Illuminate\Pagination\LengthAwarePaginator;

class ListApprovalWorkflows
{
    public function execute(): LengthAwarePaginator
    {
        return ApprovalWorkflow::query()
            ->select([
                'id',
                'code',
                'name',
                'description',
                'version',
                'is_active',
                'is_default',
                'published_at',
            ])
            ->orderBy('id', 'desc')
            ->paginate(15);
    }
}

