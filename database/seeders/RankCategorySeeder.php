<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RankCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'id' => 10,
                'name' => 'çavuş',
                'vacation_days_count' => 30,
                'contract_duration' => 36,
                'vacation_days_per_month' => 3.75,
            ],
            [
                'id' => 20,
                'name' => 'gizir',
                'vacation_days_count' => 35,
                'contract_duration' => 60,
                'vacation_days_per_month' => 3,
            ],
            [
                'id' => 30,
                'name' => 'zabit',
                'vacation_days_count' => 45,
                'contract_duration' => 18,
                'next_contract_duration' => 120,
                'vacation_days_per_month' => 2.5,
            ],
        ];

        foreach ($categories as $category) {
            \App\Models\RankCategory::create($category);
        }
    }
}
