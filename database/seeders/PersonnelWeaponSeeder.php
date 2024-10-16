<?php

namespace Database\Seeders;

use App\Models\Personnel;
use App\Models\Weapon;
use Illuminate\Database\Seeder;

class PersonnelWeaponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $weapons = Weapon::all();
        $personnel = Personnel::all();
        $currentTime = now();

        foreach ($personnel as $person) {
            // Get a random number of weapons for the person (between 1 and 3)
            $randomWeapons = $weapons->random(rand(1, 3));

            // Prepare weapon assignments in bulk
            $weaponData = [];
            foreach ($randomWeapons as $weapon) {
                $weaponData[] = [
                    'weapon_id' => $weapon->id,
                    'bullets' => rand(20, 30),
                    'chest' => 1,
                    'replacement_card' => 'S'.$person->tabel_no.$weapon->id,
                    'given_date' => $currentTime->copy()->subDays(rand(30, 365)),
                    'expire_date' => $currentTime->copy()->addDays(rand(30, 365)),
                    'return_date' => null,
                ];
            }

            // Batch insert weapon assignments
            $person->weapons()->createMany($weaponData);
        }
    }
}
