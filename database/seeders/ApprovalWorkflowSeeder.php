<?php

namespace Database\Seeders;

use App\Models\ApprovalGroup;
use App\Models\ApprovalWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Idempotent creation
            $workflow = ApprovalWorkflow::firstOrCreate(
                ['code' => 'STANDARD_KAIZEN_APPROVAL', 'version' => 1],
                [
                    'name' => 'Standart Kaizen Onay Akışı',
                    'is_active' => true,
                    'is_default' => true,
                    'published_at' => now(),
                ]
            );

            // Create stages if they don't exist for this workflow
            if ($workflow->stages()->count() === 0) {
                $workflow->stages()->createMany([
                    [
                        'code' => 'OPEX_REVIEW',
                        'name' => 'OPEX Değerlendirmesi',
                        'sequence' => 10,
                        'is_final' => false,
                        'is_active' => true,
                    ],
                    [
                        'code' => 'MANAGER_APPROVAL',
                        'name' => 'Yönetici Onayı',
                        'sequence' => 20,
                        'is_final' => false,
                        'is_active' => true,
                    ],
                    [
                        'code' => 'BOARD_APPROVAL',
                        'name' => 'Kurul Onayı',
                        'sequence' => 30,
                        'is_final' => true,
                        'is_active' => true,
                    ],
                ]);
            }

            // Create default approval groups
            $opexGroup = ApprovalGroup::firstOrCreate(
                ['code' => 'OPEX_REVIEW_GROUP'],
                ['name' => 'OPEX Değerlendirme Grubu', 'is_active' => true]
            );

            $managerGroup = ApprovalGroup::firstOrCreate(
                ['code' => 'MANAGER_APPROVAL_GROUP'],
                ['name' => 'Yönetici Onay Grubu', 'is_active' => true]
            );

            $boardGroup = ApprovalGroup::firstOrCreate(
                ['code' => 'BOARD_APPROVAL_GROUP'],
                ['name' => 'Kurul Onay Grubu', 'is_active' => true]
            );

            // Assign groups to stages
            $opexStage = $workflow->stages()->where('code', 'OPEX_REVIEW')->first();
            if ($opexStage && $opexStage->stageAssignments()->count() === 0) {
                $opexStage->stageAssignments()->create([
                    'approval_group_id' => $opexGroup->id,
                    'scope' => 'GLOBAL',
                ]);
            }

            $managerStage = $workflow->stages()->where('code', 'MANAGER_APPROVAL')->first();
            if ($managerStage && $managerStage->stageAssignments()->count() === 0) {
                $managerStage->stageAssignments()->create([
                    'approval_group_id' => $managerGroup->id,
                    'scope' => 'DEPARTMENT',
                ]);
            }

            $boardStage = $workflow->stages()->where('code', 'BOARD_APPROVAL')->first();
            if ($boardStage && $boardStage->stageAssignments()->count() === 0) {
                $boardStage->stageAssignments()->create([
                    'approval_group_id' => $boardGroup->id,
                    'scope' => 'GLOBAL',
                ]);
            }
        });
    }
}
