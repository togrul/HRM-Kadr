<?php

namespace App\Modules\Personnel\Services;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PersonnelCrudBenchmarkFixtureService
{
    public function ensureEditablePersonnel(User $user): Personnel
    {
        $referenceIds = $this->ensureReferenceData();

        return Personnel::withoutEvents(function () use ($user, $referenceIds): Personnel {
            return Personnel::query()->firstOrCreate(
                ['tabel_no' => 'CRUD-BENCH-001'],
                [
                    'surname' => 'Benchmark',
                    'name' => 'Personnel',
                    'patronymic' => 'User',
                    'birthdate' => '1990-01-01',
                    'gender' => 1,
                    'mobile' => '0500000000',
                    'nationality_id' => $referenceIds['country_id'],
                    'pin' => 'ABC1234',
                    'residental_address' => 'Benchmark address',
                    'registered_address' => 'Benchmark address',
                    'education_degree_id' => $referenceIds['education_degree_id'],
                    'structure_id' => $referenceIds['structure_id'],
                    'position_id' => $referenceIds['position_id'],
                    'work_norm_id' => $referenceIds['work_norm_id'],
                    'join_work_date' => '2020-01-01',
                    'added_by' => $user->getKey(),
                    'is_pending' => false,
                ]
            );
        });
    }

    /**
     * @return array{country_id:int,education_degree_id:int,structure_id:int,position_id:int,work_norm_id:int}
     */
    private function ensureReferenceData(): array
    {
        DB::table('countries')->updateOrInsert(['code' => 'AZ'], ['code' => 'AZ']);
        DB::table('education_degrees')->upsert(
            [[
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Бакалавр',
            ]],
            ['id'],
            ['title_az', 'title_en', 'title_ru']
        );
        DB::table('structures')->upsert(
            [[
                'id' => 1,
                'name' => 'Benchmark Structure',
                'shortname' => 'BSTR',
            ]],
            ['id'],
            ['name', 'shortname']
        );
        DB::table('positions')->upsert(
            [[
                'id' => 1,
                'name' => 'Benchmark Position',
            ]],
            ['id'],
            ['name']
        );
        DB::table('work_norms')->upsert(
            [[
                'id' => 1,
                'name_az' => 'Tam ştat',
                'name_en' => 'Full time',
                'name_ru' => 'Полная ставка',
            ]],
            ['id'],
            ['name_az', 'name_en', 'name_ru']
        );

        return [
            'country_id' => (int) DB::table('countries')->where('code', 'AZ')->value('id'),
            'education_degree_id' => (int) DB::table('education_degrees')->where('id', 1)->value('id'),
            'structure_id' => (int) DB::table('structures')->where('id', 1)->value('id'),
            'position_id' => (int) DB::table('positions')->where('id', 1)->value('id'),
            'work_norm_id' => (int) DB::table('work_norms')->where('id', 1)->value('id'),
        ];
    }
}
