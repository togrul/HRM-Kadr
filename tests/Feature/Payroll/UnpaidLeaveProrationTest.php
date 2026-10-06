<?php

namespace Tests\Feature\Payroll;

use App\Enums\OrderStatusEnum;
use App\Models\Personnel;
use App\Modules\Payroll\Application\Services\ProrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnpaidLeaveProrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_unpaid_leave_days_reduce_the_proration_factor(): void
    {
        $personnel = $this->makePersonnel('upl@example.test');

        // 22 working days, 0 unrecorded absences in the summary.
        DB::table('attendance_monthly_summaries')->insert([
            'tabel_no' => $personnel->tabel_no, 'year' => 2026, 'month' => 6,
            'total_scheduled_minutes' => 0, 'total_worked_minutes' => 0, 'total_overtime_minutes' => 0,
            'total_absence_minutes' => 0, 'total_workdays' => 22, 'total_present_days' => 22, 'total_absence_days' => 0,
        ]);

        $leaveTypeId = DB::table('leave_types')->insertGetId([
            'name' => 'Ödənişsiz', 'attendance_code' => 'UNPAID', 'max_days' => 30, 'requires_document' => false,
        ]);
        DB::table('leaves')->insert([
            'tabel_no' => $personnel->tabel_no, 'leave_type_id' => $leaveTypeId,
            'starts_at' => '2026-06-10', 'ends_at' => '2026-06-12', 'total_days' => 3,
            'status_id' => OrderStatusEnum::APPROVED->value,
        ]);

        // factor = (22 - 3) / 22.
        $this->assertSame(round(19 / 22, 4), app(ProrationService::class)->factorFor($personnel->tabel_no, 2026, 6));
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
