<?php

namespace Tests\Feature\OnboardingLibrary;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentReceipt;
use App\Models\OnboardingDocumentTemplate;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\OnboardingLibrary\Application\Services\OnboardingLibraryReadService;
use App\Modules\OnboardingLibrary\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OnboardingLibraryDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_and_assign_template_from_common_module(): void
    {
        Storage::fake('public');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-onboarding-library', 'web'),
            Permission::findOrCreate('manage-onboarding-document-templates', 'web'),
            Permission::findOrCreate('assign-onboarding-documents', 'web'),
        );

        $personnel = $this->makePersonnel('employee@example.test');
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('templateForm.title', 'Daxili qaydalar')
            ->set('templateForm.document_type', 'policy')
            ->set('templateForm.version', '1.0')
            ->set('templateUpload', UploadedFile::fake()->create('rules.pdf', 100, 'application/pdf'))
            ->call('saveTemplate')
            ->set('selectedPersonnelIds', [$personnel->id])
            ->call('assignSelected')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('onboarding_document_assignments', [
            'personnel_id' => $personnel->id,
        ]);
    }

    public function test_dashboard_can_export_templates_and_assignments(): void
    {
        Storage::fake('public');
        Excel::fake();
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-onboarding-library', 'web'),
            Permission::findOrCreate('manage-onboarding-document-templates', 'web'),
            Permission::findOrCreate('assign-onboarding-documents', 'web'),
        );

        $personnel = $this->makePersonnel('employee@example.test');
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('templateForm.title', 'Daxili qaydalar')
            ->set('templateForm.document_type', 'policy')
            ->set('templateForm.version', '1.0')
            ->set('templateForm.is_active', true)
            ->set('templateForm.auto_assign_new_hires', true)
            ->set('templateUpload', UploadedFile::fake()->create('rules.pdf', 100, 'application/pdf'))
            ->call('saveTemplate')
            ->set('selectedPersonnelIds', [$personnel->id])
            ->call('assignSelected')
            ->call('exportTemplates')
            ->call('exportAssignments');

        Excel::assertDownloaded('onboarding-templates.xlsx');
        Excel::assertDownloaded('onboarding-assignments.xlsx');
    }

    public function test_dashboard_can_export_version_history(): void
    {
        Storage::fake('public');
        Excel::fake();
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-onboarding-library', 'web'),
            Permission::findOrCreate('manage-onboarding-document-templates', 'web'),
        );

        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)
            ->set('templateForm.title', 'Daxili qaydalar')
            ->set('templateForm.document_type', 'policy')
            ->set('templateForm.version', '1.0')
            ->set('templateUpload', UploadedFile::fake()->create('rules.pdf', 100, 'application/pdf'))
            ->call('saveTemplate');

        $templateId = (int) $component->get('assignmentForm.template_id');

        $component->call('prepareNextTemplateVersion', $templateId)
            ->set('templateUpload', UploadedFile::fake()->create('rules-v2.pdf', 100, 'application/pdf'))
            ->call('saveTemplate')
            ->call('exportVersionHistory');

        Excel::assertDownloaded('onboarding-version-history.xlsx');
    }

    public function test_dashboard_can_assign_template_by_structure_targeting(): void
    {
        Storage::fake('public');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-onboarding-library', 'web'),
            Permission::findOrCreate('manage-onboarding-document-templates', 'web'),
            Permission::findOrCreate('assign-onboarding-documents', 'web'),
        );

        $personnel = $this->makePersonnel('employee@example.test');
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('templateForm.title', 'Məzuniyyət qaydaları')
            ->set('templateForm.document_type', 'policy')
            ->set('templateForm.version', '1.0')
            ->set('templateUpload', UploadedFile::fake()->create('rules.pdf', 100, 'application/pdf'))
            ->call('saveTemplate')
            ->set('selectedStructureIds', [$personnel->structure_id])
            ->call('assignSelected')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('onboarding_document_assignments', [
            'personnel_id' => $personnel->id,
        ]);
    }

    public function test_dashboard_can_toggle_template_active_state(): void
    {
        Storage::fake('public');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(
            Permission::findOrCreate('view-onboarding-library', 'web'),
            Permission::findOrCreate('manage-onboarding-document-templates', 'web'),
        );

        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)
            ->set('templateForm.title', 'İş qaydası')
            ->set('templateForm.document_type', 'policy')
            ->set('templateForm.version', '1.0')
            ->set('templateUpload', UploadedFile::fake()->create('rules.pdf', 100, 'application/pdf'))
            ->call('saveTemplate');

        $templateId = (int) $component->get('assignmentForm.template_id');

        $this->assertDatabaseHas('onboarding_document_templates', [
            'id' => $templateId,
            'is_active' => true,
        ]);

        $component->call('toggleTemplateActive', $templateId)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('onboarding_document_templates', [
            'id' => $templateId,
            'is_active' => false,
        ]);
    }

    public function test_recent_assignments_resolve_acknowledged_status_from_receipt(): void
    {
        Storage::fake('public');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view-onboarding-library', 'web'));

        $personnel = $this->makePersonnel('employee@example.test');

        $template = OnboardingDocumentTemplate::query()->create([
            'title' => 'Uyğunlaşma qaydası',
            'document_type' => 'policy',
            'version' => '1.1',
            'disk' => 'public',
            'file_path' => 'onboarding/rules.pdf',
            'is_required' => true,
            'requires_acknowledgement' => true,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $assignment = OnboardingDocumentAssignment::query()->create([
            'template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subHour(),
            'status' => 'opened',
        ]);

        OnboardingDocumentReceipt::query()->create([
            'assignment_id' => $assignment->id,
            'opened_at' => now()->subMinutes(50),
            'acknowledged_at' => now()->subMinutes(10),
        ]);

        $recentAssignment = app(OnboardingLibraryReadService::class)->build()['recent_assignments'][0];

        $this->assertSame('Təsdiqlənib', $recentAssignment['status']);
        $this->assertSame('emerald', $recentAssignment['status_mode']);
        $this->assertNotSame('—', $recentAssignment['acknowledged_at']);
    }

    public function test_dashboard_render_stays_within_query_budget(): void
    {
        Storage::fake('public');
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('view-onboarding-library', 'web'));

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
