<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WeaponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $weapons = [
            'JERICHO',
            'Glock',
            'AK-103',
            'RPK',
            'Bora',
            'Cobalt',
            'UZI',
        ];

        foreach ($weapons as $weapon) {
            \App\Models\Weapon::firstOrCreate(['name' => $weapon], [
                'capacity' => 30,
                'production_year' => 2014,
            ]);
        }
    }
}
