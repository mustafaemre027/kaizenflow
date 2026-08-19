<?php

namespace Database\Seeders;

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
        });
    }
}
