<?php

namespace Tests\Feature\LearningLibrary;

use App\Models\EmployeeContentAsset;
use App\Models\EmployeeContentAssignment;
use App\Models\EmployeeContentView;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\LearningLibrary\Application\Services\LearningLibraryReadService;
use App\Modules\LearningLibrary\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LearningLibraryDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_and_assign_asset_from_common_module(): void
    {
        Storage::fake('employee_content');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-learning-library', 'web'),
            Permission::findOrCreate('manage-employee-content-library', 'web'),
            Permission::findOrCreate('assign-employee-content', 'web'),
        );

        $personnel = $this->makePersonnel('employee@example.test');
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('assetForm.title', 'Xoş gəldin video')
            ->set('assetForm.content_type', 'pdf')
            ->set('assetUpload', UploadedFile::fake()->create('welcome.pdf', 100, 'application/pdf'))
            ->call('saveAsset')
            ->set('selectedPersonnelIds', [$personnel->id])
            ->call('assignSelected')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('employee_content_assignments', [
            'personnel_id' => $personnel->id,
        ]);
    }

    public function test_dashboard_can_export_assets_and_assignments(): void
    {
        Storage::fake('employee_content');
        Excel::fake();
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-learning-library', 'web'),
            Permission::findOrCreate('manage-employee-content-library', 'web'),
            Permission::findOrCreate('assign-employee-content', 'web'),
        );

        $personnel = $this->makePersonnel('employee@example.test');
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('assetForm.title', 'Xoş gəldin video')
            ->set('assetForm.content_type', 'pdf')
            ->set('assetForm.is_active', true)
            ->set('assetForm.auto_assign_new_hires', true)
            ->set('assetUpload', UploadedFile::fake()->create('welcome.pdf', 100, 'application/pdf'))
            ->call('saveAsset')
            ->set('selectedPersonnelIds', [$personnel->id])
            ->call('assignSelected')
            ->call('exportAssets')
            ->call('exportAssignments');

        Excel::assertDownloaded('learning-assets.xlsx');
        Excel::assertDownloaded('learning-assignments.xlsx');
    }

    public function test_dashboard_can_export_version_history(): void
    {
        Storage::fake('employee_content');
        Excel::fake();
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-learning-library', 'web'),
            Permission::findOrCreate('manage-employee-content-library', 'web'),
        );

        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)
            ->set('assetForm.title', 'Xoş gəldin video')
            ->set('assetForm.content_type', 'pdf')
            ->set('assetUpload', UploadedFile::fake()->create('welcome.pdf', 100, 'application/pdf'))
            ->call('saveAsset');

        $assetId = (int) $component->get('assignmentForm.asset_id');

        $component->call('prepareNextAssetVersion', $assetId)
            ->set('assetUpload', UploadedFile::fake()->create('welcome-v2.pdf', 100, 'application/pdf'))
            ->call('saveAsset')
            ->call('exportVersionHistory');

        Excel::assertDownloaded('learning-version-history.xlsx');
    }

    public function test_dashboard_can_assign_asset_by_structure_targeting(): void
    {
        Storage::fake('employee_content');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-learning-library', 'web'),
            Permission::findOrCreate('manage-employee-content-library', 'web'),
            Permission::findOrCreate('assign-employee-content', 'web'),
        );

        $personnel = $this->makePersonnel('employee@example.test');
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('assetForm.title', 'Təhlükəsizlik qaydaları')
            ->set('assetForm.content_type', 'pdf')
            ->set('assetUpload', UploadedFile::fake()->create('safety.pdf', 100, 'application/pdf'))
            ->call('saveAsset')
            ->set('selectedStructureIds', [$personnel->structure_id])
            ->call('assignSelected')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('employee_content_assignments', [
            'personnel_id' => $personnel->id,
        ]);
    }

    public function test_dashboard_can_toggle_asset_active_state(): void
    {
        Storage::fake('employee_content');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-learning-library', 'web'),
            Permission::findOrCreate('manage-employee-content-library', 'web'),
        );

        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)
            ->set('assetForm.title', 'Xoş gəldin video')
            ->set('assetForm.content_type', 'pdf')
            ->set('assetUpload', UploadedFile::fake()->create('welcome.pdf', 100, 'application/pdf'))
            ->call('saveAsset');

        $assetId = (int) $component->get('assignmentForm.asset_id');

        $this->assertDatabaseHas('employee_content_assets', [
            'id' => $assetId,
            'is_active' => true,
        ]);

        $component->call('toggleAssetActive', $assetId)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('employee_content_assets', [
            'id' => $assetId,
            'is_active' => false,
        ]);
    }

    public function test_recent_assignments_resolve_completed_status_from_view(): void
    {
        Storage::fake('employee_content');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view-learning-library', 'web'));

        $personnel = $this->makePersonnel('employee@example.test');

        $asset = EmployeeContentAsset::query()->create([
            'title' => 'Öyrənmək üçün',
            'content_type' => 'pdf',
            'version' => '1.0',
            'storage_disk' => 'employee_content',
            'storage_path' => 'learning/welcome.pdf',
            'is_required' => true,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $assignment = EmployeeContentAssignment::query()->create([
            'asset_id' => $asset->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subHour(),
            'status' => 'opened',
        ]);

        EmployeeContentView::query()->create([
            'assignment_id' => $assignment->id,
            'opened_at' => now()->subMinutes(40),
            'completed_at' => now()->subMinutes(5),
        ]);

        $recentAssignment = app(LearningLibraryReadService::class)->build()['recent_assignments'][0];

        $this->assertSame('Tamamlanıb', $recentAssignment['status']);
        $this->assertSame('emerald', $recentAssignment['status_mode']);
        $this->assertNotSame('—', $recentAssignment['completed_at']);
    }

    public function test_dashboard_render_stays_within_query_budget(): void
    {
        Storage::fake('employee_content');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view-learning-library', 'web'));

        $this->makePersonnel('employee@example.test');
        $this->actingAs($user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(Dashboard::class)
            ->assertOk();

        $this->assertLessThanOrEqual(16, count(DB::getQueryLog()));
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
            DB::table('positions')->insert(['id' => 1, 'name' => 'Officer']);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
    }
}
