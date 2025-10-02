<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // if (env('APP_TYPE') == 'MILITARY') {
        //     dd('military');
        // } else {
        //     dd(env('APP_TYPE'));
        // }
        $special = [
            //            PositionSeeder::class,
            //            PersonnelSeeder::class,
            //            OrderSeeder::class,
            //            StructureSeeder::class,
            //            CandidateSeeder::class,
            //            CitiesSeeder::class,
            //            GlobalSeeder::class
            //            RankCategorySeeder::class,\
        ];

        $this->call($this->fillDefaultData());

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

    }

    private function fillDefaultData(): array
    {
        return [
            // TruncateTablesSeeder::class,
            StructureSeeder::class
        ];
    }
}
