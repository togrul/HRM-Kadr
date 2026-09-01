<?php

namespace Tests\Feature\Compensation;

use App\Models\CompensationComponent;
use App\Models\CompensationRegime;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeCompensation;
use App\Models\Personnel;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\Compensation\Infrastructure\Persistence\Eloquent\EloquentCompensationReadRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompensationReadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_contract_is_bound_to_eloquent_implementation(): void
    {
        $this->assertInstanceOf(
            EloquentCompensationReadRepository::class,
            app(CompensationReadRepository::class),
        );
    }

    public function test_repository_returns_current_compensation_and_resolved_components(): void
    {
        $personnel = $this->makePersonnel('repo1@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $seniority = CompensationComponent::where('code', 'seniority')->value('id');

        $comp = EmployeeCompensation::create([
            'tabel_no' => $personnel->tabel_no,
            'regime_id' => $regimeId,
            'base_amount' => 2000,
            'currency' => 'AZN',
            'effective_from' => '2026-01-01',
            'status' => 'active',
        ]);
        $comp->lines()->create(['component_id' => $seniority, 'percent' => 10]);

        $repo = app(CompensationReadRepository::class);

        $current = $repo->currentCompensation($personnel->tabel_no);
        $this->assertNotNull($current);
        $this->assertSame('2000.00', $current->base_amount);

        $components = $repo->componentsFor($personnel->tabel_no);
        $this->assertCount(1, $components);
        $this->assertSame(200.0, $components->first()['amount']);
    }

    public function test_repository_returns_primary_bank_account_and_catalog(): void
    {
        $personnel = $this->makePersonnel('repo2@example.test');

        EmployeeBankAccount::create(['tabel_no' => $personnel->tabel_no, 'iban' => 'AZ0000000001', 'is_primary' => false, 'is_active' => true]);
        EmployeeBankAccount::create(['tabel_no' => $personnel->tabel_no, 'iban' => 'AZ0000000002', 'is_primary' => true, 'is_active' => true]);

        $repo = app(CompensationReadRepository::class);

        $this->assertSame('AZ0000000002', $repo->primaryBankAccount($personnel->tabel_no)->iban);
        $this->assertSame(11, $repo->componentCatalog()->count());
    }

    private function makePersonnel(string $email): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => $email,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
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
        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        }
        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        }
        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert(['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1]);
        }
        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert(['id' => 1, 'name' => 'Officer', 'approval_rank' => 10, 'is_approval_target' => false]);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
    }
}
