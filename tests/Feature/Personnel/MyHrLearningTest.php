<?php

namespace Tests\Feature\Personnel;

use App\Models\EmployeeContentAssignment;
use App\Models\EmployeeContentAsset;
use App\Models\EmployeeContentView;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\MyHrLearning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrLearningTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_hr_learning_tab_shows_assigned_materials(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);
        $user->givePermissionTo(
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('view-own-learning-content', 'web'),
        );

        $personnel = $this->makePersonnel($user->email);
        $asset = EmployeeContentAsset::query()->create([
            'title' => 'Xoş gəldin təqdimatı',
            'content_type' => 'presentation',
            'description' => 'İlk gün üçün qısa təqdimat.',
            'storage_disk' => 'employee_content',
            'storage_path' => 'learning-content/welcome.pdf',
            'visibility' => 'internal',
            'is_required' => true,
            'estimated_minutes' => 15,
            'created_by' => $user->id,
        ]);

        EmployeeContentAssignment::query()->create([
            'asset_id' => $asset->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subDay(),
            'due_at' => now()->addDays(3),
            'status' => 'assigned',
        ]);

        $this->actingAs($user)
            ->get(route('my-hr', ['tab' => 'learning']))
            ->assertOk()
            ->assertSee('Öyrənmə materialları')
            ->assertSee('Xoş gəldin təqdimatı')
            ->assertSee('Tamamlandı kimi işarələ');
    }

    public function test_employee_can_open_and_complete_learning_material(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);
        $user->givePermissionTo(
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('view-own-learning-content', 'web'),
        );

        $personnel = $this->makePersonnel($user->email);
        $asset = EmployeeContentAsset::query()->create([
            'title' => 'Qısa video',
            'content_type' => 'video',
            'external_url' => 'https://example.test/welcome-video',
            'visibility' => 'internal',
            'is_required' => false,
            'estimated_minutes' => 5,
            'created_by' => $user->id,
        ]);

        $assignment = EmployeeContentAssignment::query()->create([
            'asset_id' => $asset->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subHour(),
            'status' => 'assigned',
        ]);

        $this->actingAs($user);

        Livewire::test(MyHrLearning::class, ['personnelId' => $personnel->id])
            ->call('openContent', $assignment->id)
            ->assertRedirect('https://example.test/welcome-video');

        $this->assertNotNull(EmployeeContentView::query()->where('assignment_id', $assignment->id)->value('opened_at'));

        Livewire::test(MyHrLearning::class, ['personnelId' => $personnel->id])
            ->call('complete', $assignment->id)
            ->assertDispatched('notify');

        $assignment->refresh();
        $view = $assignment->view()->first();

        $this->assertSame('completed', $assignment->status);
        $this->assertNotNull($view?->completed_at);
        $this->assertSame(100, $view?->watch_progress_percent);
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
