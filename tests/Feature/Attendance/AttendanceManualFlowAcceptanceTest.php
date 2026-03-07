<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceManualEntry;
use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceOvertimeRequest;
use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceManualEntryService;
use App\Modules\Attendance\Application\Services\AttendancePunchProcessingPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceManualFlowAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_manual_entry_is_projected_to_daily_ledger_model(): void
    {
        $user = User::factory()->create();
        $personnel = $this->makePersonnel();

        /** @var AttendanceManualEntryService $service */
        $service = app(AttendanceManualEntryService::class);

        $entry = $service->upsert(
            tabelNo: $personnel->tabel_no,
            date: '2026-03-10',
            payload: [
                'worked_minutes' => 480,
                'overtime_minutes' => 60,
                'absence_code' => null,
                'reason' => 'Manual correction',
            ],
            enteredBy: $user->id
        );

        $service->approve($entry->fresh(), $user->id);

        $ledger = AttendanceDailyLedger::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->whereDate('date', '2026-03-10')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertSame('manual_present', $ledger->attendance_status);
        $this->assertSame('manual_override', $ledger->source_summary);
        $this->assertSame(480, (int) $ledger->worked_minutes);
        $this->assertSame(60, (int) $ledger->overtime_minutes);
        $this->assertSame(60, (int) data_get($ledger->meta, 'requestable_overtime_minutes'));
    }

    public function test_approved_manual_entry_auto_generates_single_pending_overtime_request(): void
    {
        $user = User::factory()->create();
        $personnel = $this->makePersonnel();

        /** @var AttendanceManualEntryService $service */
        $service = app(AttendanceManualEntryService::class);

        $entry = $service->upsert(
            tabelNo: $personnel->tabel_no,
            date: '2026-03-13',
            payload: [
                'worked_minutes' => 500,
                'overtime_minutes' => 45,
                'absence_code' => null,
                'reason' => 'Manual overtime',
            ],
            enteredBy: $user->id
        );

        $service->approve($entry->fresh(), $user->id);

        $this->assertDatabaseHas('attendance_overtime_requests', [
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-13 00:00:00',
            'requested_minutes' => 45,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        app(AttendancePunchProcessingPipelineService::class)->process(
            from: now()->parse('2026-03-13'),
            to: now()->parse('2026-03-13'),
            source: null,
            options: [
                'include_processed' => true,
                'mark_processed' => false,
                'tabel_nos' => [$personnel->tabel_no],
            ]
        );

        $this->assertSame(
            1,
            AttendanceOvertimeRequest::query()
                ->where('tabel_no', $personnel->tabel_no)
                ->whereDate('date', '2026-03-13')
                ->count()
        );
    }

    public function test_auto_calculated_manual_entry_generates_requestable_overtime_request_under_approval_policy(): void
    {
        $user = User::factory()->create();
        $personnel = $this->makePersonnel();
        $shift = AttendanceShift::query()->create([
            'name' => 'Day shift',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
            'is_night_shift' => false,
            'in_flex_before_minutes' => 0,
            'in_flex_after_minutes' => 0,
            'out_flex_before_minutes' => 0,
            'out_flex_after_minutes' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        AttendanceSetting::query()->create([
            'scope_type' => 'global',
            'scope_id' => null,
            'timezone' => 'Asia/Baku',
            'default_shift_id' => $shift->id,
            'late_grace_minutes' => 0,
            'early_leave_grace_minutes' => 0,
            'rounding_policy' => 'none',
            'rounding_step_minutes' => 5,
            'overtime_policy' => 'by_approval',
            'is_active' => true,
        ]);

        /** @var AttendanceManualEntryService $service */
        $service = app(AttendanceManualEntryService::class);

        $entry = $service->upsert(
            tabelNo: $personnel->tabel_no,
            date: '2026-03-15',
            payload: [
                'check_in_at' => '09:00',
                'check_out_at' => '21:20',
                'shift_source_mode' => 'explicit',
                'explicit_shift_id' => $shift->id,
                'absence_code' => null,
                'reason' => 'Auto generated overtime request',
            ],
            enteredBy: $user->id
        );

        $approved = $service->approve($entry->fresh(), $user->id);

        $this->assertSame(680, (int) $approved->worked_minutes);
        $this->assertSame(0, (int) $approved->overtime_minutes);

        $request = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->whereDate('date', '2026-03-15')
            ->first();

        $this->assertNotNull($request);
        $this->assertSame('pending', $request->status);
        $this->assertSame('auto_manual_entry', $request->source);
        $this->assertSame(200, (int) $request->requested_minutes);
    }

    public function test_non_approval_policy_does_not_generate_overtime_request(): void
    {
        AttendanceSetting::query()->create([
            'scope_type' => 'global',
            'scope_id' => null,
            'timezone' => 'Asia/Baku',
            'late_grace_minutes' => 0,
            'early_leave_grace_minutes' => 0,
            'rounding_policy' => 'none',
            'rounding_step_minutes' => 5,
            'overtime_policy' => 'after_shift',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $personnel = $this->makePersonnel();

        /** @var AttendanceManualEntryService $service */
        $service = app(AttendanceManualEntryService::class);

        $entry = $service->upsert(
            tabelNo: $personnel->tabel_no,
            date: '2026-03-14',
            payload: [
                'worked_minutes' => 500,
                'overtime_minutes' => 45,
                'absence_code' => null,
                'reason' => 'No approval policy',
            ],
            enteredBy: $user->id
        );

        $service->approve($entry->fresh(), $user->id);

        $this->assertDatabaseMissing('attendance_overtime_requests', [
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-14',
        ]);
    }

    public function test_manual_override_workflow_writes_attendance_audit_trail(): void
    {
        $user = User::factory()->create();
        $personnel = $this->makePersonnel();

        /** @var AttendanceManualEntryService $service */
        $service = app(AttendanceManualEntryService::class);

        $approvedEntry = $service->upsert(
            tabelNo: $personnel->tabel_no,
            date: '2026-03-11',
            payload: [
                'worked_minutes' => 420,
                'overtime_minutes' => 0,
                'absence_code' => null,
                'reason' => 'Approval path',
            ],
            enteredBy: $user->id
        );
        $service->approve($approvedEntry->fresh(), $user->id);

        $rejectedEntry = $service->upsert(
            tabelNo: $personnel->tabel_no,
            date: '2026-03-12',
            payload: [
                'worked_minutes' => 0,
                'overtime_minutes' => 0,
                'absence_code' => 'absent',
                'reason' => 'Rejection path',
            ],
            enteredBy: $user->id
        );
        $service->reject($rejectedEntry->fresh(), $user->id, 'Invalid evidence');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'attendance',
            'event' => 'manual_entry.created',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'subject_type' => AttendanceManualEntry::class,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'attendance',
            'event' => 'manual_entry.approved',
            'description' => 'Manual attendance entry approved.',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'subject_type' => AttendanceManualEntry::class,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'attendance',
            'event' => 'manual_entry.rejected',
            'description' => 'Manual attendance entry rejected.',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'subject_type' => AttendanceManualEntry::class,
        ]);
    }

    private function makePersonnel(array $overrides = []): Personnel
    {
        $user = User::query()->first() ?? User::factory()->create();

        $country = Country::query()->first() ?? Country::query()->create([
            'id' => 1,
            'code' => 'AZ',
        ]);

        if (! EducationDegree::query()->whereKey(1)->exists()) {
            EducationDegree::query()->create([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bakalavr',
            ]);
        }

        if (! WorkNorm::query()->whereKey(1)->exists()) {
            WorkNorm::query()->create([
                'id' => 1,
                'name_az' => 'Tam',
                'name_en' => 'Full',
                'name_ru' => 'Polniy',
            ]);
        }

        $structure = Structure::query()->first() ?? Structure::query()->create([
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);

        $position = Position::query()->first() ?? Position::query()->create([
            'id' => 1,
            'name' => 'Officer',
        ]);

        $payload = array_merge([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'John',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => $country->id,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => $user->id,
            'is_pending' => false,
        ], $overrides);

        return Personnel::withoutEvents(fn () => Personnel::query()->create($payload));
    }
}
