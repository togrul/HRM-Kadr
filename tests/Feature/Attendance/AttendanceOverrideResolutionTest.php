<?php

namespace Tests\Feature\Attendance;

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceCalendar;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Leave;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceDayContextResolverService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceOverrideResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_structure_scope_calendar_override_takes_precedence_over_global_rule(): void
    {
        $structure = $this->makeStructure('HQ');
        $personnel = $this->makePersonnel([
            'structure_id' => $structure->id,
        ]);

        AttendanceCalendar::query()->create([
            'date' => '2026-03-09',
            'day_type' => 'holiday',
            'name' => 'Global holiday',
            'is_paid' => true,
            'scope_type' => 'global',
            'scope_id' => null,
        ]);

        AttendanceCalendar::query()->create([
            'date' => '2026-03-09',
            'day_type' => 'workday',
            'name' => 'Structure workday',
            'is_paid' => true,
            'scope_type' => 'structure',
            'scope_id' => $structure->id,
        ]);

        $resolver = app(AttendanceDayContextResolverService::class);
        $context = $resolver->build(
            from: Carbon::parse('2026-03-09')->startOfDay(),
            to: Carbon::parse('2026-03-09')->endOfDay(),
            tabelNos: new Collection([$personnel->tabel_no]),
            structureByTabel: [$personnel->tabel_no => $structure->id]
        );

        $resolved = $resolver->resolveCalendarDayType(
            Carbon::parse('2026-03-09'),
            $structure->id,
            $context['calendars_global'],
            $context['calendars_structure']
        );

        $this->assertSame('workday', $resolved);
    }

    public function test_leave_override_has_higher_priority_than_vacation_and_business_trip(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $personnel = $this->makePersonnel();
        $this->seedOrderStatuses();

        Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => null,
            'starts_at' => '2026-03-10',
            'ends_at' => '2026-03-10',
            'status_id' => OrderStatusEnum::APPROVED->value,
            'approved_at' => '2026-03-09 10:00:00',
        ]));

        PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Baku',
            'duration' => 1,
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-10',
            'return_work_date' => '2026-03-11',
            'order_given_by' => 'HR',
            'order_no' => 'VAC-1',
            'order_date' => '2026-03-09 00:00:00',
            'added_by' => $user->id,
        ]);

        PersonnelBusinessTrip::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'location' => 'Ganja',
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-10',
            'description' => 'Trip',
            'order_given_by' => 'HR',
            'order_no' => 'BT-1',
            'order_date' => '2026-03-09 00:00:00',
            'added_by' => $user->id,
        ]);

        $resolver = app(AttendanceDayContextResolverService::class);
        $context = $resolver->build(
            from: Carbon::parse('2026-03-10')->startOfDay(),
            to: Carbon::parse('2026-03-10')->endOfDay(),
            tabelNos: new Collection([$personnel->tabel_no]),
            structureByTabel: [$personnel->tabel_no => $personnel->structure_id]
        );

        $override = $resolver->resolveOverride(
            Carbon::parse('2026-03-10'),
            $personnel->tabel_no,
            $context['overrides']
        );

        $this->assertNotNull($override);
        $this->assertSame('leave', $override['type'] ?? null);
        $this->assertSame('leave', $override['source'] ?? null);
    }

    public function test_partial_leave_override_carries_duration_metadata(): void
    {
        $personnel = $this->makePersonnel();
        $this->seedOrderStatuses();

        Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => null,
            'starts_at' => '2026-03-11',
            'ends_at' => '2026-03-11',
            'duration_unit' => 'hour',
            'starts_time' => '10:00',
            'ends_time' => '12:30',
            'total_minutes' => 150,
            'status_id' => OrderStatusEnum::APPROVED->value,
            'approved_at' => '2026-03-10 10:00:00',
        ]));

        $resolver = app(AttendanceDayContextResolverService::class);
        $context = $resolver->build(
            from: Carbon::parse('2026-03-11')->startOfDay(),
            to: Carbon::parse('2026-03-11')->endOfDay(),
            tabelNos: new Collection([$personnel->tabel_no]),
            structureByTabel: [$personnel->tabel_no => $personnel->structure_id]
        );

        $override = $resolver->resolveOverride(
            Carbon::parse('2026-03-11'),
            $personnel->tabel_no,
            $context['overrides']
        );

        $this->assertNotNull($override);
        $this->assertSame('leave', $override['type'] ?? null);
        $this->assertSame('hour', $override['duration_unit'] ?? null);
        $this->assertSame('10:00', $override['starts_time'] ?? null);
        $this->assertSame('12:30', $override['ends_time'] ?? null);
        $this->assertSame(150, $override['total_minutes'] ?? null);
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

    private function makeStructure(string $name): Structure
    {
        return Structure::query()->create([
            'name' => $name,
            'shortname' => $name,
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => random_int(100, 999),
            'level' => 1,
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

        $structure = Structure::query()->first() ?? $this->makeStructure('HQ');

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
