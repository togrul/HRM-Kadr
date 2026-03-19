<?php

namespace Tests\Feature\Attendance;

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceDailyLedger;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Leave;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceDayContextResolverService;
use App\Modules\Attendance\Application\Services\AttendancePuantajReadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceLeaveAndPuantajActiveWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_leave_with_legacy_approved_at_does_not_create_attendance_override(): void
    {
        $personnel = $this->makePersonnel([
            'join_work_date' => '2026-03-01',
        ]);

        $this->seedOrderStatuses();

        Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => null,
            'starts_at' => '2026-03-10',
            'ends_at' => '2026-03-12',
            'status_id' => OrderStatusEnum::CANCELLED->value,
            'approved_at' => '2026-03-09 10:00:00',
        ]));

        $context = app(AttendanceDayContextResolverService::class)->build(
            from: Carbon::parse('2026-03-01')->startOfMonth(),
            to: Carbon::parse('2026-03-31')->endOfMonth(),
            tabelNos: new Collection([$personnel->tabel_no]),
            structureByTabel: [$personnel->tabel_no => $personnel->structure_id]
        );

        $this->assertSame([], $context['override_keys_by_tabel'][$personnel->tabel_no] ?? []);
        $this->assertNull(
            app(AttendanceDayContextResolverService::class)->resolveOverride(
                Carbon::parse('2026-03-10'),
                $personnel->tabel_no,
                $context['overrides']
            )
        );
    }

    public function test_puantaj_includes_personnel_active_for_any_part_of_the_selected_month(): void
    {
        $activeWithinMonth = $this->makePersonnel([
            'name' => 'Active',
            'surname' => 'Window',
            'join_work_date' => '2026-01-01',
            'leave_work_date' => '2026-03-20',
        ]);

        $inactiveBeforeMonth = $this->makePersonnel([
            'name' => 'Inactive',
            'surname' => 'Window',
            'join_work_date' => '2025-01-01',
            'leave_work_date' => '2026-02-28',
        ]);

        $service = app(AttendancePuantajReadService::class);
        $from = Carbon::parse('2026-03-01')->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $page = $service->paginatePersonnels('', 50, [], $from, $to);
        $tabelNos = $page->getCollection()->pluck('tabel_no')->all();

        $this->assertContains($activeWithinMonth->tabel_no, $tabelNos);
        $this->assertNotContains($inactiveBeforeMonth->tabel_no, $tabelNos);
    }

    public function test_puantaj_ledger_map_exposes_partial_leave_duration_metadata(): void
    {
        $personnel = $this->makePersonnel([
            'join_work_date' => '2026-03-01',
        ]);

        AttendanceDailyLedger::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-14',
            'scheduled_minutes' => 360,
            'worked_minutes' => 355,
            'break_minutes' => 0,
            'overtime_minutes' => 0,
            'late_minutes' => 5,
            'early_leave_minutes' => 0,
            'attendance_status' => 'present',
            'absence_code' => null,
            'source_summary' => 'policy_override',
            'is_locked' => false,
            'meta' => [
                'leave_type_id' => 5,
                'leave_type_name' => 'Saatlıq icazə',
                'leave_type_code' => 'SI',
                'duration_unit' => 'hour',
                'starts_time' => '09:00',
                'ends_time' => '11:00',
                'total_minutes' => 120,
                'covered_leave_minutes' => 120,
            ],
        ]);

        $service = app(AttendancePuantajReadService::class);
        $ledgerMap = $service->loadLedgerMap(
            [$personnel->tabel_no],
            Carbon::parse('2026-03-01')->startOfMonth(),
            Carbon::parse('2026-03-31')->endOfMonth()
        );

        $cell = $ledgerMap[$personnel->tabel_no]['2026-03-14'] ?? null;

        $this->assertNotNull($cell);
        $this->assertSame('SI', $cell['leave_type_code']);
        $this->assertSame('hour', $cell['duration_unit']);
        $this->assertSame('09:00', $cell['starts_time']);
        $this->assertSame('11:00', $cell['ends_time']);
        $this->assertSame(120, $cell['total_minutes']);
        $this->assertSame(120, $cell['covered_leave_minutes']);
    }

    public function test_leave_override_follows_approve_cancel_reapprove_lifecycle(): void
    {
        $personnel = $this->makePersonnel([
            'join_work_date' => '2026-03-01',
        ]);

        $this->seedOrderStatuses();

        $leave = Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => null,
            'starts_at' => '2026-03-10',
            'ends_at' => '2026-03-12',
            'status_id' => OrderStatusEnum::APPROVED->value,
            'approved_at' => '2026-03-09 10:00:00',
        ]));

        $resolver = app(AttendanceDayContextResolverService::class);

        $approvedContext = $resolver->build(
            from: Carbon::parse('2026-03-01')->startOfMonth(),
            to: Carbon::parse('2026-03-31')->endOfMonth(),
            tabelNos: new Collection([$personnel->tabel_no]),
            structureByTabel: [$personnel->tabel_no => $personnel->structure_id]
        );

        $this->assertSame('leave', $resolver->resolveOverride(
            Carbon::parse('2026-03-10'),
            $personnel->tabel_no,
            $approvedContext['overrides']
        )['type'] ?? null);

        $leave->forceFill([
            'status_id' => OrderStatusEnum::CANCELLED->value,
            'approved_at' => null,
        ])->save();

        $cancelledContext = $resolver->build(
            from: Carbon::parse('2026-03-01')->startOfMonth(),
            to: Carbon::parse('2026-03-31')->endOfMonth(),
            tabelNos: new Collection([$personnel->tabel_no]),
            structureByTabel: [$personnel->tabel_no => $personnel->structure_id]
        );

        $this->assertNull($resolver->resolveOverride(
            Carbon::parse('2026-03-10'),
            $personnel->tabel_no,
            $cancelledContext['overrides']
        ));

        $leave->forceFill([
            'status_id' => OrderStatusEnum::APPROVED->value,
            'approved_at' => '2026-03-09 12:00:00',
        ])->save();

        $reapprovedContext = $resolver->build(
            from: Carbon::parse('2026-03-01')->startOfMonth(),
            to: Carbon::parse('2026-03-31')->endOfMonth(),
            tabelNos: new Collection([$personnel->tabel_no]),
            structureByTabel: [$personnel->tabel_no => $personnel->structure_id]
        );

        $this->assertSame('leave', $resolver->resolveOverride(
            Carbon::parse('2026-03-10'),
            $personnel->tabel_no,
            $reapprovedContext['overrides']
        )['type'] ?? null);
    }

    private function seedOrderStatuses(): void
    {
        foreach ([
            [OrderStatusEnum::PENDING->value, 'Pending'],
            [OrderStatusEnum::APPROVED->value, 'Approved'],
            [OrderStatusEnum::CANCELLED->value, 'Cancelled'],
        ] as [$id, $name]) {
            OrderStatus::query()->firstOrCreate([
                'id' => $id,
                'locale' => 'en',
            ], [
                'name' => $name,
            ]);
        }
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
