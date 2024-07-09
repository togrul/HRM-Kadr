<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'id' => 1,
                'name' => 'Xidmət rəisi'
            ],
            [
                'id' => 2,
                'name' => 'Xidmət rəisinin 1-ci müavini'
            ],
            [
                'id' => 3,
                'name' => 'Xidmət rəisinin 2-ci müavini'
            ],
            [
                'id' => 10,
                'name' => 'İdarə rəisi'
            ],
            [
                'id' => 11,
                'name' => 'İdarə rəisinin müavini'
            ],
            [
                'id' => 12,
                'name' => 'İdarə rəisinin 2-ci müavini'
            ],
            [
                'id' => 20,
                'name' => 'Tabor komandiri'
            ],
            [
                'id' => 100,
                'name' => 'Şöbə müdiri'
            ],
            [
                'id' => 200,
                'name' => 'Bölük komandiri'
            ],
            [
                'id' => 110,
                'name' => 'Bölmə rəisi'
            ],
            [
                'id' => 210,
                'name' => 'Taqım komandiri'
            ],
            [
                'id' => 220,
                'name' => 'Manqa komandiri'
            ],
            [
                'id' => 1000,
                'name' => 'Proqramçı'
            ],
            [
                'id' => 1010,
                'name' => 'Şəbəkə inzibatçısı'
            ],
            [
                'id' => 1010,
                'name' => 'Əməliyyatçı'
            ]
        ];

        foreach($positions as $ps)
        {
            Position::updateOrCreate([
                'id' => $ps['id']
            ],$ps);
        }
    }
}
