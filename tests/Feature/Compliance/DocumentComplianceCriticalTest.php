<?php

namespace Tests\Feature\Compliance;

use App\Models\User;
use App\Modules\Compliance\Application\Services\DocumentExpiryReadService;
use App\Modules\Compliance\Livewire\DocumentExpiryDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Audit 08.10.2026: missing mandatory documents were not critical ("Kritik: 0" next to 185
 * missing rows), an ID card did not satisfy the passport requirement, and dismissed staff
 * were still asked for documents.
 */
class DocumentComplianceCriticalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-08 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_critical_counts_expired_and_missing_mandatory_documents(): void
    {
        $this->seedPersonnel('CR0001');
        $this->seedPersonnel('CR0002');

        // CR0001: expired passport, card expiring in 10 days, valid contract.
        $this->insertDocuments('CR0001', passportExpires: '2026-09-01', cardExpires: '2026-10-18', contractEnds: '2028-01-01');
        // CR0002: nothing at all -> three missing mandatory documents.

        $summary = app(DocumentExpiryReadService::class)->dashboard()['summary'];

        $this->assertSame(1, $summary['expired']);
        $this->assertSame(3, $summary['missing']);
        $this->assertSame(1, $summary['expiring_30']);
        $this->assertSame(4, $summary['critical']);
        // 2 healthy (expiring card + valid contract) out of 6 required rows.
        $this->assertSame(33, $summary['compliance_score']);
    }

    public function test_an_identity_card_satisfies_the_passport_requirement(): void
    {
        $this->seedPersonnel('ID0001');
        $this->seedPersonnel('ID0002');

        DB::table('personnel_identity_documents')->insert([
            'tabel_no' => 'ID0001',
            'nationality_id' => 1,
            'series' => 'AA',
            'number' => '1234567',
            'pin' => '5ABCDEF',
            'born_country_id' => 1,
            'born_city_id' => 1,
            'height' => 175,
        ]);

        $missingPassports = app(DocumentExpiryReadService::class)
            ->rows(['type' => 'passport', 'status' => 'missing'])
            ->pluck('tabel_no')
            ->all();

        $this->assertSame(['ID0002'], $missingPassports);
    }

    public function test_dismissed_and_pending_personnel_owe_no_documents(): void
    {
        $this->seedPersonnel('AC0001');
        $this->seedPersonnel('AC0002', ['leave_work_date' => '2026-09-30']);
        $this->seedPersonnel('AC0003', ['is_pending' => true]);
        $this->seedPersonnel('AC0004', ['leave_work_date' => '2026-12-31']);
        $this->insertDocuments('AC0002', passportExpires: '2026-01-01', cardExpires: '2026-01-01', contractEnds: '2026-01-01');

        $tabelNos = app(DocumentExpiryReadService::class)->rows()->pluck('tabel_no')->unique()->sort()->values()->all();

        $this->assertSame(['AC0001', 'AC0004'], $tabelNos);
    }

    public function test_dashboard_shows_the_critical_tile_and_keeps_the_status_column_in_a_four_column_table(): void
    {
        $this->seedPersonnel('UI0001');
        $user = User::factory()->create();
        grantAllStructures($user);
        $user->givePermissionTo(Permission::findOrCreate('show-document-compliance', 'web'));

        $html = Livewire::actingAs($user)->test(DocumentExpiryDashboard::class)
            ->assertSee(__('compliance::documents.summary.critical'))
            ->assertSee(__('compliance::documents.summary.critical_hint'))
            ->assertDontSee(__('compliance::documents.columns.days_left'))
            ->html();

        $this->assertMatchesRegularExpression('/compliance-metric-critical[^>]*>.*?3/s', $html);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedPersonnel(string $tabelNo, array $overrides = []): void
    {
        $userId = DocumentExpiryFixture::seedPersonnelReferences();
        DB::table('structures')->insertOrIgnore(['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1]);
        DB::table('positions')->insertOrIgnore(['id' => 1, 'name' => 'Officer']);
        DB::table('ranks')->insertOrIgnore(['id' => 1, 'name_az' => 'Mütəxəssis', 'name_en' => 'Specialist', 'name_ru' => 'Specialist', 'is_active' => true]);

        DB::table('personnels')->insert(array_merge([
            'tabel_no' => $tabelNo,
            'surname' => 'Surname '.$tabelNo,
            'name' => 'Name',
            'patronymic' => 'Patronymic',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.$tabelNo,
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-01-01',
            'added_by' => $userId,
            'is_pending' => false,
        ], $overrides));
    }

    private function insertDocuments(string $tabelNo, string $passportExpires, string $cardExpires, string $contractEnds): void
    {
        $stamp = ['created_at' => now(), 'updated_at' => now()];

        DB::table('personnel_passports')->insert(['tabel_no' => $tabelNo, 'serial_number' => 'P-'.$tabelNo, 'given_date' => '2020-01-01', 'valid_date' => $passportExpires] + $stamp);
        DB::table('personnel_cards')->insert(['tabel_no' => $tabelNo, 'card_number' => 'C-'.$tabelNo, 'valid_date' => $cardExpires] + $stamp);
        DB::table('personnel_contracts')->insert([
            'tabel_no' => $tabelNo,
            'rank_id' => 1,
            'contract_date' => '2026-01-01',
            'contract_refresh_date' => '2026-01-01',
            'contract_duration' => 12,
            'contract_ends_at' => $contractEnds,
        ] + $stamp);
    }
}
