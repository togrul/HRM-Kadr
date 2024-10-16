<?php

namespace Database\Seeders;

use App\Models\Candidate;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CandidateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $surnames = [
            'Əhmədov',
            'Hüseynov',
            'Əliyeva',
            'Mustafayeva',
            'Rəhimov',
            'Məmmədov',
            'Nərimanlı',
            'Ələkbərova',
            'İbrahimova',
            'Quliyev',
            'Məhərrəmli',
            'Qarayeva',
            'Soltanov',
            'Babayeva',
        ];

        $patronymics = [
            'Əli',
            'Məmməd',
            'Namiq',
            'Nurlan',
            'Rasim',
            'Elçin',
            'Fərhad',
            'Səməd',
            'Cavid',
            'İlkin',
            'Rəşad',
            'Vüqar',
            'Tural',
            'Orxan',
        ];

        $names = [
            'Əli',
            'Məmməd',
            'Nərgiz',
            'Nigar',
            'Fərid',
            'Səməd',
            'Aysel',
            'Ayşən',
            'Elmira',
            'Nurlan',
            'Rəşid',
            'Aynur',
            'Cavid',
            'Nəzrin',
        ];

        $genders = [1, 1, 2, 2, 1, 1, 2, 2, 2, 1, 1, 2, 1, 2];

        $faker = Factory::create();

        foreach ($names as $key => $name) {
            Candidate::create([
                'surname' => $surnames[$key],
                'patronymic' => $patronymics[$key],
                'name' => $name,
                'structure_id' => rand(3, 23),
                'height' => rand(170, 190),
                'military_service' => Arr::random(['DQ', 'MN', 'FHN', 'DSX', 'DTX', 'yoxdur']),
                'status_id' => 30,
                'phone' => $faker->phoneNumber,
                'birthdate' => $faker->date,
                'gender' => $genders[$key],
                'knowledge_test' => rand(2, 5),
                'physical_fitness_exam' => rand(2, 5),
                'research_date' => Carbon::now()->subDays($key),
                'research_result' => 'müsbət',
                'discrediting_information' => 'yoxdur',
                'examination_date' => Carbon::now()->subDays($key),
                'appeal_date' => Carbon::now()->subDays($key),
                'application_date' => Carbon::now()->subDays($key),
                'requisition_date' => Carbon::now()->subDays($key),
                'initial_documents' => 'tamdir',
                'documents_completeness' => 'tamdir',
                'attitude_to_military' => 'h/m',
                'characteristics' => 'musbet',
                'hhk_date' => Carbon::now()->subDays($key + rand(1, 5)),
                'hhk_result' => 'yararli',
                'presented_by' => 'TI',
                'creator_id' => 1,
            ]);
        }
    }
}
