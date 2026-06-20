<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\ApprovalRouteResolverService;
use App\Services\HrPolicies\HrPolicyPackService;
use App\Services\Profiles\ProfileState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApprovalRouteResolverPolicyPackTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_uses_single_level_default_approval_policy(): void
    {
        $this->seedReferenceData();

        $employee = $this->makePersonnel('employee.public@example.test', 'Əli', 'Əli', 'Oğlu', 1, 1);
        $primary = $this->makePersonnel('chief.public@example.test', 'Rəis', 'Birinci', 'Oğlu', 1, 2);
        $upper = $this->makePersonnel('director.public@example.test', 'Rəis', 'İkinci', 'Oğlu', 1, 3);

        $this->activateProfile('public');

        $route = app(ApprovalRouteResolverService::class)->resolve($employee, 'vacation');

        $this->assertSame($primary->id, $route['approver_personnel_id']);
        $this->assertNull($route['fallback_approver_personnel_id']);
        $this->assertTrue($route['hr_always_included']);
    }

    public function test_military_profile_uses_two_level_default_approval_policy(): void
    {
        $this->seedReferenceData();

        $employee = $this->makePersonnel('employee.military@example.test', 'Əsgər', 'Bir', 'Oğlu', 1, 1);
        $primary = $this->makePersonnel('chief.military@example.test', 'Bölük', 'Rəisi', 'Oğlu', 1, 2);
        $upper = $this->makePersonnel('director.military@example.test', 'İdarə', 'Rəisi', 'Oğlu', 1, 3);

        $this->activateProfile('military');

        $route = app(ApprovalRouteResolverService::class)->resolve($employee, 'business_trip');

        $this->assertSame($primary->id, $route['approver_personnel_id']);
        $this->assertSame($upper->id, $route['fallback_approver_personnel_id']);
        $this->assertTrue($route['hr_always_included']);
    }

    public function test_policy_pack_can_shape_module_tabs_and_test_subtabs(): void
    {
        config()->set('hr_policies.packs.military.workflow_defaults.training_needs.tabs', ['overview', 'planning', 'results']);
        config()->set('hr_policies.packs.military.workflow_defaults.performance_evaluation.tabs', ['overview', 'tests', 'reports']);
        config()->set('hr_policies.packs.military.workflow_defaults.performance_evaluation.test_tabs', ['banks', 'review']);

        $this->activateProfile('military');

        $policies = app(HrPolicyPackService::class);

        $this->assertSame(
            ['overview', 'planning', 'results'],
            $policies->workflowTabs('training_needs', ['overview', 'catalogs', 'planning', 'results'])
        );

        $this->assertSame(
            ['overview', 'tests', 'reports'],
            $policies->workflowTabs('performance_evaluation', ['overview', 'cycles', 'tests', 'reports'])
        );

        $this->assertSame(
            ['banks', 'review'],
            $policies->workflowTestTabs('performance_evaluation', ['banks', 'questions', 'import', 'sessions', 'review'])
        );
    }

    public function test_policy_pack_can_disable_global_self_service_review_override(): void
    {
        config()->set('hr_policies.packs.public.permission_flags.self_service_reviews.review_all', false);

        $this->activateProfile('public');

        $this->assertFalse(
            app(HrPolicyPackService::class)->permissionEnabled('self_service_reviews.review_all')
        );
    }

    private function activateProfile(string $profile): void
    {
        config()->set('profiles.active', $profile);

        $this->app->forgetInstance(ProfileState::class);
        $this->app->forgetInstance(HrPolicyPackService::class);
        $this->app->forgetInstance(ApprovalRouteResolverService::class);
    }

    private function makePersonnel(string $email, string $surname, string $name, string $patronymic, int $structureId, int $positionId): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname,
            'name' => $name,
            'patronymic' => $patronymic,
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => $email,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => $structureId,
            'position_id' => $positionId,
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
            DB::table('positions')->insert(['id' => 1, 'name' => 'Specialist', 'approval_rank' => 10, 'is_approval_target' => false]);
        }
        if (! DB::table('positions')->where('id', 2)->exists()) {
            DB::table('positions')->insert(['id' => 2, 'name' => 'Section Chief', 'approval_rank' => 20, 'is_approval_target' => true]);
        }
        if (! DB::table('positions')->where('id', 3)->exists()) {
            DB::table('positions')->insert(['id' => 3, 'name' => 'Director', 'approval_rank' => 30, 'is_approval_target' => true]);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
    }
}
