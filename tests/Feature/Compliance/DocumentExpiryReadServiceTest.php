<?php

namespace Tests\Feature\Compliance;

use App\Models\Personnel;
use App\Modules\Compliance\Application\Services\DocumentExpiryReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentExpiryReadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_summarizes_expired_and_expiring_documents(): void
    {
        Carbon::setTestNow('2026-04-30 10:00:00');

        $personnel = $this->makePersonnel();
        $personnelWithoutRequiredDocs = $this->makePersonnel('DCMISS');

        DB::table('personnel_cards')->insert([
            'tabel_no' => $personnel->tabel_no,
            'card_number' => 'CARD-1',
            'valid_date' => '2026-05-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('personnel_passports')->insert([
            'tabel_no' => $personnel->tabel_no,
            'serial_number' => 'PASS-1',
            'given_date' => '2025-01-01',
            'valid_date' => '2026-04-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('personnel_contracts')->insert([
            'tabel_no' => $personnel->tabel_no,
            'rank_id' => 1,
            'contract_date' => '2026-01-01',
            'contract_refresh_date' => '2026-01-01',
            'contract_duration' => 36,
            'contract_ends_at' => '2028-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('personnel_cards')->insert([
            'tabel_no' => $personnelWithoutRequiredDocs->tabel_no,
            'card_number' => 'CARD-2',
            'valid_date' => '2028-05-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = app(DocumentExpiryReadService::class)->dashboard();

        $this->assertTrue(Schema::hasTable('compliance_document_requirements'));
        $this->assertSame(6, $payload['summary']['total']);
        $this->assertSame(1, $payload['summary']['expired']);
        $this->assertSame(1, $payload['summary']['expiring_30']);
        $this->assertSame(2, $payload['summary']['missing']);
        $this->assertSame(50, $payload['summary']['compliance_score']);
        $this->assertSame(['expired'], app(DocumentExpiryReadService::class)->rows(['status' => 'expired'])->pluck('status')->unique()->values()->all());
        $this->assertSame(['passport'], app(DocumentExpiryReadService::class)->rows(['type' => 'passport'])->pluck('document_type')->unique()->values()->all());
        $this->assertSame(['contract'], app(DocumentExpiryReadService::class)->rows(['type' => 'contract', 'status' => 'missing'])->pluck('document_type')->unique()->values()->all());
        $this->assertSame(1, $payload['structureScores']->count());
        $this->assertSame(50, $payload['structureScores']->first()['score']);

        $service = app(DocumentExpiryReadService::class);
        $this->assertSame(4, $service->reminderRows(30)->count());
        $this->assertSame([
            'personnel',
            'tabel_no',
            'structure',
            'position',
            'document_type',
            'document_number',
            'expires_at',
            'days_left',
            'status',
        ], array_keys($service->exportRows(['status' => 'expired'])->first()));
    }

    private function makePersonnel(?string $prefix = null): Personnel
    {
        $this->seedReferenceData();

        $prefix ??= 'DC'.Str::upper(Str::random(6));

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => $prefix,
            'surname' => 'Compliance',
            'name' => 'Employee',
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'document-compliance@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'DC'.str_pad((string) random_int(1, 99999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('country_translations')->insertOrIgnore([
            'id' => 1,
            'country_id' => 1,
            'locale' => 'az',
            'title' => 'Azərbaycan',
        ]);
        DB::table('education_degrees')->insertOrIgnore([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 1,
            'name' => 'Compliance HQ',
            'shortname' => 'CHQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);
        DB::table('positions')->insertOrIgnore([
            'id' => 1,
            'name' => 'Compliance Officer',
        ]);
        DB::table('ranks')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Baş mütəxəssis',
            'name_en' => 'Chief specialist',
            'name_ru' => 'Chief specialist',
            'is_active' => true,
        ]);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
    }
}
