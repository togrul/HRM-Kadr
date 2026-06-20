<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonnelActiveAbsenceRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_vacation_relation_prefers_current_vacation_over_newer_future_one(): void
    {
        $this->seedReferenceData();

        $personnel = $this->makePersonnel('active-vacation@example.test');

        PersonnelVacation::withoutEvents(fn () => PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Baku',
            'duration' => 5,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'return_work_date' => now()->addDays(2)->toDateString(),
            'order_given_by' => 'HR',
            'order_no' => 'VAC-ACTIVE',
            'order_date' => now()->subDays(3)->toDateString(),
            'vacation_days_total' => 20,
            'remaining_days' => 15,
            'added_by' => 1,
        ]));

        PersonnelVacation::withoutEvents(fn () => PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Ganja',
            'duration' => 7,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(16)->toDateString(),
            'return_work_date' => now()->addDays(17)->toDateString(),
            'order_given_by' => 'HR',
            'order_no' => 'VAC-FUTURE',
            'order_date' => now()->toDateString(),
            'vacation_days_total' => 20,
            'remaining_days' => 13,
            'added_by' => 1,
        ]));

        $fresh = Personnel::query()
            ->with(['latestVacation', 'hasActiveVacation'])
            ->findOrFail($personnel->id);

        $this->assertSame('VAC-FUTURE', $fresh->latestVacation?->order_no);
        $this->assertSame('VAC-ACTIVE', $fresh->hasActiveVacation?->order_no);
        $this->assertSame('VAC-ACTIVE', $fresh->activeVacation?->order_no);
    }

    public function test_active_business_trip_relation_prefers_current_trip_over_newer_future_one(): void
    {
        $this->seedReferenceData();

        $personnel = $this->makePersonnel('active-trip@example.test');

        PersonnelBusinessTrip::withoutEvents(function () use ($personnel) {
            $trip = new PersonnelBusinessTrip;
            $trip->forceFill([
                'tabel_no' => $personnel->tabel_no,
                'location' => 'Baku',
                'description' => 'Meeting',
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'order_given_by' => 'HR',
                'order_no' => 'TRIP-ACTIVE',
                'order_date' => now()->subDays(2)->toDateString(),
                'added_by' => 1,
            ])->save();

            $futureTrip = new PersonnelBusinessTrip;
            $futureTrip->forceFill([
                'tabel_no' => $personnel->tabel_no,
                'location' => 'Ganja',
                'description' => 'Audit',
                'start_date' => now()->addDays(10)->toDateString(),
                'end_date' => now()->addDays(13)->toDateString(),
                'order_given_by' => 'HR',
                'order_no' => 'TRIP-FUTURE',
                'order_date' => now()->toDateString(),
                'added_by' => 1,
            ])->save();
        });

        $fresh = Personnel::query()
            ->with(['latestBusinessTrip', 'hasActiveBusinessTrip'])
            ->findOrFail($personnel->id);

        $this->assertSame('TRIP-FUTURE', $fresh->latestBusinessTrip?->order_no);
        $this->assertSame('TRIP-ACTIVE', $fresh->hasActiveBusinessTrip?->order_no);
        $this->assertSame('TRIP-ACTIVE', $fresh->activeBusinessTrip?->order_no);
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
            'join_work_date' => now()->subMonth()->toDateString(),
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        }
        if (! DB::table('country_translations')->where('id', 1)->exists()) {
            DB::table('country_translations')->insert(['id' => 1, 'country_id' => 1, 'locale' => 'az', 'title' => 'Azərbaycan']);
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
