<?php

namespace Tests\Feature\Orders;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\HireOrderRevocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * İşə qəbulun geri alınmasını bloklayan qeydlər hər moduldan EmployeeRecordSource ilə gəlir;
 * işə qəbulun özünün yaratdıqları (avtomatik əmək haqqı layihəsi, sistem tabel günü) sayılmır.
 */
class HireRevocationDependentRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_hire_has_no_dependent_records(): void
    {
        $personnel = $this->makePersonnel();

        DB::table('employee_compensations')->insert($this->compensation($personnel, 'draft', 'auto: hire'));
        DB::table('attendance_daily_ledgers')->insert([
            'tabel_no' => $personnel->tabel_no, 'date' => '2026-10-01', 'scheduled_minutes' => 480,
            'worked_minutes' => 0, 'attendance_status' => 'absent', 'source_summary' => 'system',
        ]);

        $this->assertSame([], app(HireOrderRevocation::class)->dependentRecords($personnel));
    }

    public function test_records_from_other_modules_block_the_revocation(): void
    {
        $personnel = $this->makePersonnel();

        DB::table('attendance_raw_punches')->insert(['tabel_no' => $personnel->tabel_no, 'punched_at' => '2026-10-01 09:00:00']);
        DB::table('employee_compensations')->insert($this->compensation($personnel, 'active', null));
        DB::table('employee_lifecycle_events')->insert([
            'personnel_id' => $personnel->id, 'tabel_no' => $personnel->tabel_no, 'type' => 'probation',
            'status' => 'in_progress', 'title' => 'Sınaq müddəti', 'effective_date' => '2026-10-01',
        ]);

        $records = app(HireOrderRevocation::class)->dependentRecords($personnel);

        $this->assertContains(__('orders::order_composer.hire_revoke.records.attendance_punches').': 1', $records);
        $this->assertContains(__('orders::order_composer.hire_revoke.records.compensations').': 1', $records);
        $this->assertContains(__('orders::order_composer.hire_revoke.records.lifecycle_events').': 1', $records);
        $this->assertCount(3, $records);
    }

    /**
     * @return array<string, mixed>
     */
    private function compensation(Personnel $personnel, string $status, ?string $note): array
    {
        $regimeId = DB::table('compensation_regimes')->insertGetId(['code' => 'r'.$status, 'name' => 'Rejim', 'is_active' => true]);

        return [
            'tabel_no' => $personnel->tabel_no, 'regime_id' => $regimeId, 'base_amount' => 1000,
            'currency' => 'AZN', 'effective_from' => '2026-10-01', 'status' => $status, 'note' => $note,
        ];
    }

    private function makePersonnel(): Personnel
    {
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Ali', 'title_en' => 'Higher', 'title_ru' => '-']);
        DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => '-']);
        DB::table('structures')->insert(['id' => 1, 'name' => 'Baş ofis', 'shortname' => 'BO', 'code' => 1, 'level' => 1, 'coefficient' => 1]);
        DB::table('positions')->insert(['id' => 1, 'name' => 'Mühasib', 'level' => 6]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'H-001', 'surname' => 'Yeni', 'name' => 'İşçi', 'patronymic' => 'Test',
            'birthdate' => '1990-01-01', 'gender' => 1, 'mobile' => '0500000000', 'nationality_id' => 1,
            'pin' => 'H001', 'residental_address' => 'Bakı', 'education_degree_id' => 1, 'structure_id' => 1,
            'position_id' => 1, 'work_norm_id' => 1, 'join_work_date' => '2026-10-01',
            'added_by' => User::factory()->create()->id,
        ]));
    }
}
