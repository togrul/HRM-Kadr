<?php

namespace Tests\Feature\Compliance;

use Illuminate\Support\Facades\DB;

/**
 * A mixed document-compliance dataset: every status boundary (-1, 0, 30, 31, 60, 61 days),
 * open-ended contracts, zero-length contracts, duplicate documents, look-alike names,
 * an unnamed structure/position, soft-deleted personnel and an extra required document
 * type with no source table (always "missing").
 */
class DocumentExpiryFixture
{
    public static function seedMixed(int $personnel = 30, string $prefix = 'P'): void
    {
        self::seedReferenceData();

        $userId = DB::table('users')->insertGetId([
            'name' => 'Fixture Owner',
            'email' => 'compliance-fixture-'.uniqid().'@example.test',
            'password' => 'x',
            'is_active' => true,
        ]);

        $rows = [];
        for ($i = 1; $i <= $personnel; $i++) {
            $rows[] = [
                'tabel_no' => sprintf('%s%06d', $prefix, $i),
                'surname' => in_array($i, [3, 4], true) ? 'Twin' : 'Surname'.$i,
                'name' => $i % 7 === 0 ? '' : (in_array($i, [3, 4], true) ? 'Same' : 'Name'.$i),
                'patronymic' => $i % 5 === 0 ? '' : 'Patronymic',
                'birthdate' => '1990-01-01',
                'gender' => 1,
                'mobile' => '994501112233',
                'nationality_id' => 1,
                'pin' => sprintf('%s%06d', $prefix, $i),
                'residental_address' => 'Main st',
                'education_degree_id' => 1,
                'structure_id' => [1, 2, 3][$i % 3],
                'position_id' => $i % 4 === 0 ? 2 : 1,
                'work_norm_id' => 1,
                'join_work_date' => '2026-01-01',
                'added_by' => $userId,
                'is_pending' => false,
                'deleted_at' => $i % 11 === 0 ? now()->toDateTimeString() : null,
            ];
        }
        DB::table('personnels')->insert($rows);

        $offsets = [-400, -1, 0, 1, 30, 31, 59, 60, 61, 500];
        $date = fn (int $days): string => today()->addDays($days)->toDateString();
        $cards = [];
        $passports = [];
        $contracts = [];

        for ($i = 1; $i <= $personnel; $i++) {
            $tabelNo = sprintf('%s%06d', $prefix, $i);

            if ($i % 2 === 0) {
                $cards[] = ['tabel_no' => $tabelNo, 'card_number' => 'CARD-'.$i, 'valid_date' => $date($offsets[$i % 10])];
            }
            if ($i === 2) {
                $cards[] = ['tabel_no' => $tabelNo, 'card_number' => 'CARD-2B', 'valid_date' => $date(-1)];
            }
            if ($i % 3 === 0) {
                $passports[] = ['tabel_no' => $tabelNo, 'serial_number' => 'AZE'.$i, 'given_date' => '2020-01-01', 'valid_date' => $date($offsets[($i + 3) % 10])];
            }
            if ($i % 4 !== 1) {
                $contracts[] = [
                    'tabel_no' => $tabelNo,
                    'rank_id' => $i % 2 === 0 ? 1 : 2,
                    'contract_date' => '2026-01-01',
                    'contract_refresh_date' => '2026-01-01',
                    'contract_duration' => $i % 6 === 0 ? 0 : 12,
                    'contract_ends_at' => $i % 5 === 0 ? null : $date($offsets[($i + 7) % 10]),
                ];
            }
        }

        $stamp = ['created_at' => now(), 'updated_at' => now()];
        DB::table('personnel_cards')->insert(array_map(fn (array $row): array => $row + $stamp, $cards));
        DB::table('personnel_passports')->insert(array_map(fn (array $row): array => $row + $stamp, $passports));
        DB::table('personnel_contracts')->insert(array_map(fn (array $row): array => $row + $stamp, $contracts));

        DB::table('compliance_document_requirements')->insertOrIgnore([
            ['key' => 'medical', 'label_az' => 'Tibbi arayış', 'label_en' => null, 'is_required' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'visa', 'label_az' => 'Viza', 'label_en' => 'Visa', 'is_required' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private static function seedReferenceData(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Full']);

        foreach ([1 => 'Alpha HQ', 2 => 'Beta Unit', 3 => ''] as $id => $name) {
            DB::table('structures')->insertOrIgnore(['id' => $id, 'name' => $name, 'shortname' => 'S'.$id, 'parent_id' => null, 'coefficient' => 1.10, 'code' => 40 + $id, 'level' => 1]);
        }

        DB::table('positions')->insertOrIgnore([['id' => 1, 'name' => 'Inspector'], ['id' => 2, 'name' => '']]);
        DB::table('ranks')->insertOrIgnore([
            ['id' => 1, 'name_az' => 'Baş mütəxəssis', 'name_en' => 'Chief', 'name_ru' => 'Chief', 'is_active' => true],
            ['id' => 2, 'name_az' => '', 'name_en' => '', 'name_ru' => '', 'is_active' => true],
        ]);
    }
}
