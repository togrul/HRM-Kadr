<?php

namespace Tests\Feature\Personnel;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentTemplate;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\OnboardingAssignmentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OnboardingAssignmentManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_template_and_assign_onboarding_document(): void
    {
        Storage::fake('public');
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo([
            Permission::findOrCreate('assign-onboarding-documents', 'web'),
            Permission::findOrCreate('manage-onboarding-document-templates', 'web'),
        ]);

        $personnel = $this->makePersonnel('employee@example.test');

        Livewire::actingAs($hr)
            ->test(OnboardingAssignmentManager::class, ['personnelModel' => $personnel->id])
            ->set('templateForm.title', 'İş yeri qaydaları')
            ->set('templateForm.document_type', 'policy')
            ->set('templateForm.version', '1.0')
            ->set('templateForm.is_required', true)
            ->set('templateForm.requires_acknowledgement', true)
            ->set('templateUpload', UploadedFile::fake()->create('rules.pdf', 120, 'application/pdf'))
            ->call('saveTemplate')
            ->assertDispatched('notify')
            ->set('assignmentForm.template_id', OnboardingDocumentTemplate::query()->value('id'))
            ->set('assignmentForm.due_at', '2026-04-05')
            ->call('assignTemplate')
            ->assertDispatched('notify');

        $template = OnboardingDocumentTemplate::query()->first();
        $assignment = OnboardingDocumentAssignment::query()->first();

        $this->assertNotNull($template);
        $this->assertNotNull($assignment);
        $this->assertSame($personnel->id, $assignment->personnel_id);
        $this->assertSame('assigned', $assignment->status);
        Storage::disk('public')->assertExists($template->file_path);
    }

    public function test_hr_can_waive_and_remove_onboarding_assignment(): void
    {
        Storage::fake('public');
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo(Permission::findOrCreate('assign-onboarding-documents', 'web'));

        $personnel = $this->makePersonnel('employee@example.test');
        $template = OnboardingDocumentTemplate::query()->create([
            'title' => 'Vəzifə təlimatı',
            'document_type' => 'job_instruction',
            'version' => '1.0',
            'file_path' => 'onboarding-documents/instruction.pdf',
            'disk' => 'public',
            'is_required' => true,
            'requires_acknowledgement' => true,
        ]);

        $assignment = OnboardingDocumentAssignment::query()->create([
            'template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $hr->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        Livewire::actingAs($hr)
            ->test(OnboardingAssignmentManager::class, ['personnelModel' => $personnel->id])
            ->call('waiveAssignment', $assignment->id)
            ->assertDispatched('notify');

        $this->assertSame('waived', $assignment->fresh()->status);

        Livewire::actingAs($hr)
            ->test(OnboardingAssignmentManager::class, ['personnelModel' => $personnel->id])
            ->call('removeAssignment', $assignment->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('onboarding_document_assignments', ['id' => $assignment->id]);
    }

    private function makePersonnel(?string $email): Personnel
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
        Permission::findOrCreate('assign-onboarding-documents', 'web');
        Permission::findOrCreate('manage-onboarding-document-templates', 'web');

        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        }

        if (! DB::table('country_translations')->where('id', 1)->exists()) {
            DB::table('country_translations')->insert([
                'id' => 1,
                'country_id' => 1,
                'locale' => 'az',
                'title' => 'Azərbaycan',
            ]);
        }

        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bachelor',
            ]);
        }

        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert([
                'id' => 1,
                'name' => 'HQ',
                'shortname' => 'HQ',
                'parent_id' => null,
                'coefficient' => 1.10,
                'code' => 10,
                'level' => 1,
            ]);
        }

        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert([
                'id' => 1,
                'name' => 'Officer',
            ]);
        }

        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert([
                'id' => 1,
                'name_az' => 'Tam iş günü',
                'name_en' => 'Full time',
                'name_ru' => 'Full time',
            ]);
        }
    }
}
