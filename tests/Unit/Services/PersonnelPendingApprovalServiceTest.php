<?php

namespace Tests\Unit\Services;

use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\PersonnelLaborActivity;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Services\PersonnelPendingApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonnelPendingApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_pending_personnel_and_creates_current_labor_activity(): void
    {
        $personnel = $this->makePersonnel(isPending: true, joinDate: '2026-02-01');

        app(PersonnelPendingApprovalService::class)->approve($personnel);

        $personnel->refresh();

        $this->assertFalse((bool) $personnel->is_pending);
        $this->assertSame('2026-02-01', (string) optional($personnel->join_work_date)->format('Y-m-d'));

        $this->assertDatabaseHas('personnel_labor_activities', [
            'tabel_no' => $personnel->tabel_no,
            'position' => 'Officer',
            'is_current' => 1,
            'leave_date' => null,
        ]);
    }

    public function test_it_does_not_create_duplicate_current_labor_activity(): void
    {
        $personnel = $this->makePersonnel(isPending: true, joinDate: '2026-02-02');

        PersonnelLaborActivity::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'company_name' => 'Existing',
            'position' => 'Officer',
            'coefficient' => 1.15,
            'join_date' => '2026-02-02',
            'is_special_service' => true,
            'is_current' => true,
        ]);

        app(PersonnelPendingApprovalService::class)->approve($personnel);

        $this->assertSame(
            1,
            PersonnelLaborActivity::query()
                ->where('tabel_no', $personnel->tabel_no)
                ->where('is_current', true)
                ->whereNull('leave_date')
                ->count()
        );
    }

    private function makePersonnel(bool $isPending, string $joinDate): Personnel
    {
        $user = User::factory()->create();

        $country = Country::query()->create([
            'id' => 1,
            'code' => 'AZ',
        ]);
        EducationDegree::query()->create([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bakalavr',
        ]);
        WorkNorm::query()->create([
            'id' => 1,
            'name_az' => 'Tam',
            'name_en' => 'Full',
            'name_ru' => 'Polniy',
        ]);
        $structure = Structure::query()->create([
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.15,
            'code' => 10,
            'level' => 1,
        ]);
        $position = Position::query()->create([
            'id' => 1,
            'name' => 'Officer',
        ]);

        return Personnel::withoutEvents(function () use ($user, $country, $structure, $position, $isPending, $joinDate) {
            return Personnel::query()->create([
                'tabel_no' => 'TB'.Str::upper(Str::random(6)),
                'surname' => 'Doe',
                'name' => 'John',
                'patronymic' => 'Smith',
                'birthdate' => '1990-01-01',
                'gender' => 1,
                'mobile' => '994501112233',
                'nationality_id' => $country->id,
                'pin' => 'P1234567',
                'residental_address' => 'Main st',
                'education_degree_id' => 1,
                'structure_id' => $structure->id,
                'position_id' => $position->id,
                'work_norm_id' => 1,
                'join_work_date' => $joinDate,
                'added_by' => $user->id,
                'is_pending' => $isPending,
            ]);
        });
    }
}

