<?php

namespace Database\Seeders;

use App\Models\Weapon;
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

        $rows = array_map(fn ($weapon) => [
            'name' => $weapon,
            'capacity' => 30,
            'production_year' => 2014,
        ], $weapons);

        Weapon::upsert($rows, ['name'], ['capacity', 'production_year']);
    }
}
