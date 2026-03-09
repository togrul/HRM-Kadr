<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceDailyLedger;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceDayContextResolverService;
use App\Modules\Attendance\Application\Services\AttendanceCalendarManagementService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceCalendarSyncAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_rule_save_and_delete_recalculate_same_day_ledger(): void
    {
        $user = User::factory()->create();
        $personnel = $this->makePersonnel([
            'join_work_date' => '2026-03-01',
        ]);

        /** @var AttendanceCalendarManagementService $service */
        $service = app(AttendanceCalendarManagementService::class);

        $calendar = $service->upsert([
            'date' => '2026-03-09',
            'day_type' => 'holiday',
            'name' => '8 mart',
            'is_paid' => true,
            'scope_type' => 'global',
            'scope_id' => null,
        ], $user->id);

        $this->assertDatabaseHas('attendance_calendars', [
            'id' => $calendar->id,
            'date' => '2026-03-09 00:00:00',
            'day_type' => 'holiday',
            'scope_type' => 'global',
        ]);

        $context = app(AttendanceDayContextResolverService::class)->build(
            from: Carbon::parse('2026-03-09')->startOfDay(),
            to: Carbon::parse('2026-03-09')->endOfDay(),
            tabelNos: new Collection([$personnel->tabel_no]),
            structureByTabel: [$personnel->tabel_no => $personnel->structure_id]
        );

        $this->assertSame('holiday', $context['calendars_global']['2026-03-09'] ?? null);

        $holidayLedger = AttendanceDailyLedger::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->whereDate('date', '2026-03-09')
            ->first();

        $this->assertNotNull($holidayLedger);
        $this->assertSame('holiday', $holidayLedger->attendance_status);
        $this->assertSame(0, (int) $holidayLedger->worked_minutes);

        $service->delete($calendar->fresh(), $user->id);

        $workdayLedger = AttendanceDailyLedger::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->whereDate('date', '2026-03-09')
            ->first();

        $this->assertNotNull($workdayLedger);
        $this->assertSame('absent', $workdayLedger->attendance_status);
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
