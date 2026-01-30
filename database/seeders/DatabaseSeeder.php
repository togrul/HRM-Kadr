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
        $appType = (string) config('app.app_type', env('APP_TYPE', 'public'));
        $generalSeeders = [
           PersonnelSeeder::class,
           GlobalSeeder::class,
           OrderSeeder::class,
           StructureSeeder::class,
           CitiesSeeder::class,
           LeaveTypeSeeder::class,
        ];

        $militarySeeders = [
            PositionSeeder::class,
            CandidateSeeder::class,
            RankCategorySeeder::class,
            WeaponSeeder::class,
            PersonnelWeaponSeeder::class,
        ];

        $this->call($appType === 'military' ? array_merge($militarySeeders, $generalSeeders) : $generalSeeders);
    }
}
