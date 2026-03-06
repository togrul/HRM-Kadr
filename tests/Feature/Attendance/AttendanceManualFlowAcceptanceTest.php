<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceManualEntry;
use App\Models\AttendanceDailyLedger;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceManualEntryService;
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
