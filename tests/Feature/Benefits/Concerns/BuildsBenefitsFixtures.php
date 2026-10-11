<?php

namespace Tests\Feature\Benefits\Concerns;

use App\Models\BenefitPlan;
use App\Models\EmployeeCompensation;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Benefits\Application\Services\BenefitPlanService;
use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use App\Services\Profiles\ProfileState;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * İmtiyaz testləri üçün ortaq məlumat: strukturlar (alt struktur daxil), vəzifələr (VTİSK
 * kateqoriyası ilə), əməkdaşlar, qohumluq qeydləri, kompensasiya və paketlər.
 */
trait BuildsBenefitsFixtures
{
    protected function seedBenefitsReferenceData(): void
    {
        foreach ([
            'countries' => [['id' => 1, 'code' => 'AZ']],
            'education_degrees' => [['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']],
            'structures' => [
                ['id' => 1, 'name' => 'Baş idarə', 'shortname' => 'BI', 'parent_id' => null, 'coefficient' => 1, 'code' => 10, 'level' => 1],
                ['id' => 2, 'name' => 'İT şöbəsi', 'shortname' => 'IT', 'parent_id' => 1, 'coefficient' => 1, 'code' => 11, 'level' => 2],
                ['id' => 3, 'name' => 'Maliyyə', 'shortname' => 'MAL', 'parent_id' => null, 'coefficient' => 1, 'code' => 20, 'level' => 1],
            ],
            'positions' => [
                ['id' => 1, 'name' => 'Mütəxəssis', 'approval_rank' => 10, 'is_approval_target' => false, 'vtisk_category' => 'mutexessis'],
                ['id' => 2, 'name' => 'Şöbə müdiri', 'approval_rank' => 20, 'is_approval_target' => false, 'vtisk_category' => 'rehber'],
            ],
            'work_norms' => [['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Full']],
            'kinships' => [
                ['id' => 1, 'name_az' => 'Həyat yoldaşı', 'name_en' => 'Spouse', 'name_ru' => 'Супруг(а)', 'is_active' => true],
                ['id' => 2, 'name_az' => 'Övladı', 'name_en' => 'Child', 'name_ru' => 'Ребёнок', 'is_active' => true],
            ],
        ] as $table => $rows) {
            foreach ($rows as $row) {
                DB::table($table)->insertOrIgnore($row);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function benefitsPersonnel(string $tabelNo, array $overrides = []): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create(array_merge([
            'tabel_no' => $tabelNo, 'surname' => 'Soyad'.$tabelNo, 'name' => 'Ad', 'patronymic' => 'Ata',
            'birthdate' => '1990-01-01', 'gender' => 1, 'mobile' => '994501112233', 'nationality_id' => 1,
            'pin' => 'P'.substr(md5($tabelNo), 0, 6), 'residental_address' => 'Bakı', 'education_degree_id' => 1,
            'structure_id' => 2, 'position_id' => 1, 'work_norm_id' => 1, 'join_work_date' => '2025-01-01',
            'contract_type' => 'indefinite', 'added_by' => 1, 'is_pending' => false,
        ], $overrides)));
    }

    protected function benefitsKinship(string $tabelNo, string $name, int $kinshipId = 1): int
    {
        return (int) DB::table('personnel_kinships')->insertGetId([
            'tabel_no' => $tabelNo,
            'kinship_id' => $kinshipId,
            'fullname' => $name,
            'birthdate' => '1992-05-05',
            'registered_address' => 'Bakı',
            'residental_address' => 'Bakı',
        ]);
    }

    protected function benefitsCompensation(string $tabelNo, float $base = 1500.0): EmployeeCompensation
    {
        $regimeId = DB::table('compensation_regimes')->where('code', 'private')->value('id')
            ?? DB::table('compensation_regimes')->orderBy('sort')->value('id');

        return EmployeeCompensation::query()->create([
            'tabel_no' => $tabelNo,
            'regime_id' => $regimeId,
            'base_amount' => $base,
            'currency' => 'AZN',
            'effective_from' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function benefitsPlan(string $code, string $type = 'medical_insurance', array $overrides = []): BenefitPlan
    {
        return app(BenefitPlanService::class)->save(array_merge([
            'code' => $code,
            'name' => 'Paket '.$code,
            'type' => $type,
            'monthly_cost' => 100,
            'employer_share_percent' => 80,
            'allows_dependents' => true,
            'dependent_monthly_cost' => 50,
            'dependent_employer_share_percent' => 50,
            'max_dependents' => 3,
            'self_service' => true,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @param  list<string>  $permissions
     * @param  list<int>|null  $structures  null — bütün strukturlar
     */
    protected function benefitsUser(array $permissions, ?array $structures = null): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $structures === null ? grantAllStructures($user) : grantStructures($user, $structures);

        return $user;
    }

    /** Quraşdırma profilini (şirkət növünü) dəyişir — APP_TYPE-ın test ekvivalenti. */
    protected function useProfile(string $profile): void
    {
        $this->app->instance(ProfileState::class, new ProfileState(
            config('profiles.profiles', []),
            $profile,
            config('modules.catalog', []),
        ));
        $this->app->forgetInstance(BenefitsAvailability::class);
        $this->app->forgetScopedInstances();
    }
}
