<?php

namespace Tests\Feature\Personnel;

use App\Models\EmployeeContentAsset;
use App\Models\EmployeeContentAssignment;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\LearningAssignmentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LearningAssignmentManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_learning_asset_and_assign_it(): void
    {
        Storage::fake('employee_content');
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo([
            Permission::findOrCreate('assign-employee-content', 'web'),
            Permission::findOrCreate('manage-employee-content-library', 'web'),
        ]);

        $personnel = $this->makePersonnel('employee@example.test');

        Livewire::actingAs($hr)
            ->test(LearningAssignmentManager::class, ['personnelModel' => $personnel->id])
            ->set('assetForm.title', 'Xoş gəldin pdf')
            ->set('assetForm.content_type', 'pdf')
            ->set('assetForm.description', 'İlk gün paketi')
            ->set('assetForm.visibility', 'internal')
            ->set('assetForm.is_required', true)
            ->set('assetForm.estimated_minutes', 20)
            ->set('assetUpload', UploadedFile::fake()->create('welcome.pdf', 120, 'application/pdf'))
            ->call('saveAsset')
            ->assertDispatched('notify')
            ->set('assignmentForm.asset_id', EmployeeContentAsset::query()->value('id'))
            ->set('assignmentForm.due_at', '2026-04-10')
            ->call('assignAsset')
            ->assertDispatched('notify');

        $asset = EmployeeContentAsset::query()->first();
        $assignment = EmployeeContentAssignment::query()->first();

        $this->assertNotNull($asset);
        $this->assertNotNull($assignment);
        $this->assertSame($personnel->id, $assignment->personnel_id);
        Storage::disk('employee_content')->assertExists($asset->storage_path);
    }

    public function test_hr_can_waive_and_remove_learning_assignment(): void
    {
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo(Permission::findOrCreate('assign-employee-content', 'web'));

        $personnel = $this->makePersonnel('employee@example.test');
        $asset = EmployeeContentAsset::query()->create([
            'title' => 'Orientation link',
            'content_type' => 'link',
            'external_url' => 'https://example.test/orientation',
            'visibility' => 'internal',
        ]);

        $assignment = EmployeeContentAssignment::query()->create([
            'asset_id' => $asset->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $hr->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        Livewire::actingAs($hr)
            ->test(LearningAssignmentManager::class, ['personnelModel' => $personnel->id])
            ->call('waiveAssignment', $assignment->id)
            ->assertDispatched('notify');

        $this->assertSame('waived', $assignment->fresh()->status);

        Livewire::actingAs($hr)
            ->test(LearningAssignmentManager::class, ['personnelModel' => $personnel->id])
            ->call('removeAssignment', $assignment->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('employee_content_assignments', ['id' => $assignment->id]);
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
        Permission::findOrCreate('assign-employee-content', 'web');
        Permission::findOrCreate('manage-employee-content-library', 'web');

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
