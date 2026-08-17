<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['code' => 'OPS', 'name' => 'Operasyonlar'],
            ['code' => 'QLT', 'name' => 'Kalite'],
            ['code' => 'MNT', 'name' => 'Bakım'],
            ['code' => 'LOG', 'name' => 'Lojistik'],
            ['code' => 'IT', 'name' => 'Bilgi Teknolojileri'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                [
                    'name' => $department['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
