<?php

namespace App\Modules\Personnel\Services;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PersonnelCrudBenchmarkFixtureService
{
    public function ensureEditablePersonnel(User $user): Personnel
    {
        $this->ensureReferenceData();

        return Personnel::withoutEvents(function () use ($user): Personnel {
            return Personnel::query()->firstOrCreate(
                ['tabel_no' => 'CRUD-BENCH-001'],
                [
                    'surname' => 'Benchmark',
                    'name' => 'Personnel',
                    'patronymic' => 'User',
                    'birthdate' => '1990-01-01',
                    'gender' => 1,
                    'mobile' => '0500000000',
                    'nationality_id' => 1,
                    'pin' => 'ABC1234',
                    'residental_address' => 'Benchmark address',
                    'registered_address' => 'Benchmark address',
                    'education_degree_id' => 1,
                    'structure_id' => 1,
                    'position_id' => 1,
                    'work_norm_id' => 1,
                    'join_work_date' => '2020-01-01',
                    'added_by' => $user->getKey(),
                    'is_pending' => false,
                ]
            );
        });
    }

    private function ensureReferenceData(): void
    {
        DB::table('countries')->upsert([['id' => 1, 'code' => 'AZ']], ['id'], ['code']);
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
    }
}
