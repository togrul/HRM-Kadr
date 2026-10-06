<?php

namespace Tests\Feature\Compensation;

use App\Models\CompensationRegime;
use App\Models\EmployeeCompensation;
use App\Models\PayGrade;
use App\Models\PayScale;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransferRegradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_transfer_seeds_a_draft_regrade_when_new_position_maps_to_a_grade(): void
    {
        $personnel = $this->makePersonnel('xfer@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $service = app(CompensationService::class);

        $service->assignCompensation($personnel->tabel_no, ['regime_id' => $regimeId, 'base_amount' => 1000, 'effective_from' => '2026-01-01']);

        $scale = PayScale::create(['name' => 'S', 'regime_id' => $regimeId, 'currency' => 'AZN', 'effective_from' => '2026-01-01', 'is_active' => true]);
        PayGrade::create(['pay_scale_id' => $scale->id, 'code' => 'G2', 'name' => 'Chief', 'base_amount' => 2500, 'position_id' => 1, 'sort' => 1]);

        $draft = $service->suggestRegradeFromTransfer($personnel->tabel_no, 1, 'AM-XFER');

        $this->assertNotNull($draft);
        $this->assertSame('draft', $draft->status);
        $this->assertSame('2500.00', $draft->base_amount);
        $this->assertSame('auto: transfer', $draft->note);

        // Reverse removes the suggestion.
        $service->removeTransferSuggestion('AM-XFER');
        $this->assertSame(0, EmployeeCompensation::where('note', 'auto: transfer')->count());
    }

    public function test_no_regrade_when_position_has_no_grade(): void
    {
        $personnel = $this->makePersonnel('xfer2@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $service = app(CompensationService::class);
        $service->assignCompensation($personnel->tabel_no, ['regime_id' => $regimeId, 'base_amount' => 1000, 'effective_from' => '2026-01-01']);

        $this->assertNull($service->suggestRegradeFromTransfer($personnel->tabel_no, 999, 'AM-NONE'));
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
