<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['code' => 'PROCESS', 'name' => 'Süreç İyileştirme'],
            ['code' => 'QUALITY', 'name' => 'Kalite İyileştirme'],
            ['code' => 'SAFETY', 'name' => 'İş Güvenliği'],
            ['code' => 'COST', 'name' => 'Maliyet Azaltma'],
            ['code' => 'TIME', 'name' => 'Zaman Tasarrufu'],
            ['code' => 'ENV', 'name' => 'Çevresel Sürdürülebilirlik'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
