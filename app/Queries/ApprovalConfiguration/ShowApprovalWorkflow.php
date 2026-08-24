<?php

namespace App\Queries\ApprovalConfiguration;

use App\Models\ApprovalWorkflow;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowApprovalWorkflow
{
    public function execute(int $id): ApprovalWorkflow
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
            ->with(['stages' => function ($query) {
                $query->select([
                    'id',
                    'approval_workflow_id',
                    'code',
                    'name',
                    'description',
                    'sequence',
                    'is_final',
                    'is_active',
                ])->orderBy('sequence', 'asc');
            }])
            ->findOrFail($id);
    }
}

