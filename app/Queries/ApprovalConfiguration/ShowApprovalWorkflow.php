<?php

namespace App\Queries\ApprovalConfiguration;

use App\Models\ApprovalWorkflow;

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
                'approver_resolution_mode',
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
                ])->orderBy('sequence', 'asc')
                ->with(['approverRule' => function ($ruleQuery) {
                    $ruleQuery->select([
                        'id',
                        'approval_stage_id',
                        'capability',
                        'scope_source',
                        'is_active',
                    ]);
                }]);
            }])
            ->findOrFail($id);
    }
}
