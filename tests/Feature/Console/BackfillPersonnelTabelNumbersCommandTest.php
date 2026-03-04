<?php

namespace Tests\Feature\Console;

use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\PersonnelLaborActivity;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackfillPersonnelTabelNumbersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_nmzd_tabel_no_for_approved_personnel_and_updates_counter(): void
    {
        config()->set('app.company', 'PTX');

        // Seed existing sequence for same company-year.
        $this->makePersonnel([
            'tabel_no' => 'PTX-26-000007',
            'is_pending' => false,
            'join_work_date' => '2026-01-15',
            'pin' => 'P1111111',
        ]);

        $personnel = $this->makePersonnel([
            'tabel_no' => 'NMZD501',
            'is_pending' => false,
            'join_work_date' => '2026-03-01',
            'pin' => 'P2222222',
        ]);

        PersonnelLaborActivity::query()->create([
            'tabel_no' => 'NMZD501',
            'company_name' => 'PTX',
            'position' => 'Officer',
            'coefficient' => 1.10,
            'join_date' => '2026-03-01',
            'is_special_service' => true,
            'is_current' => true,
        ]);

        $this->artisan('personnel:tabel-no:backfill')
            ->assertSuccessful();

        $personnel->refresh();

        $this->assertSame('PTX-26-000008', $personnel->tabel_no);
        $this->assertDatabaseHas('personnel_tabel_no_counters', [
            'company_code' => 'PTX',
            'year' => 2026,
            'last_sequence' => 8,
        ]);
    }

    public function test_it_does_not_touch_pending_personnel(): void
    {
        config()->set('app.company', 'PTX');

        $pending = $this->makePersonnel([
            'tabel_no' => 'NMZD777',
            'is_pending' => true,
            'join_work_date' => '2026-03-05',
            'pin' => 'P3333333',
        ]);

        $this->artisan('personnel:tabel-no:backfill')
            ->assertSuccessful();

        $pending->refresh();

        $this->assertTrue((bool) $pending->is_pending);
        $this->assertSame('NMZD777', $pending->tabel_no);
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
