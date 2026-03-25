<?php

namespace Tests\Feature\Personnel;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentReceipt;
use App\Models\OnboardingDocumentTemplate;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\MyHrOnboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_hr_onboarding_tab_shows_assigned_documents(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);
        $user->givePermissionTo(
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('view-own-onboarding-documents', 'web'),
            Permission::findOrCreate('acknowledge-own-onboarding-documents', 'web'),
        );

        $personnel = $this->makePersonnel($user->email);
        $template = OnboardingDocumentTemplate::query()->create([
            'title' => 'Daxili nizam-intizam qaydaları',
            'document_type' => 'internal_regulation',
            'version' => '2.1',
            'file_path' => 'onboarding/rules.pdf',
            'disk' => 'public',
            'is_required' => true,
            'requires_acknowledgement' => true,
            'created_by' => $user->id,
        ]);

        OnboardingDocumentAssignment::query()->create([
            'template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subDay(),
            'due_at' => now()->addDays(3),
            'status' => 'assigned',
        ]);

        $this->actingAs($user)
            ->get(route('my-hr', ['tab' => 'onboarding']))
            ->assertOk()
            ->assertSee('Uyğunlaşma sənədləri')
            ->assertSee('Daxili nizam-intizam qaydaları')
            ->assertSee('Tanış oldum');
    }

    public function test_employee_can_open_and_acknowledge_onboarding_document(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);
        $user->givePermissionTo(
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('view-own-onboarding-documents', 'web'),
            Permission::findOrCreate('acknowledge-own-onboarding-documents', 'web'),
        );

        $personnel = $this->makePersonnel($user->email);
        $template = OnboardingDocumentTemplate::query()->create([
            'title' => 'İş yeri qaydaları',
            'document_type' => 'policy',
            'version' => '1.0',
            'file_path' => 'onboarding/policy.pdf',
            'disk' => 'public',
            'is_required' => true,
            'requires_acknowledgement' => true,
            'created_by' => $user->id,
        ]);

        $assignment = OnboardingDocumentAssignment::query()->create([
            'template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subHours(2),
            'due_at' => now()->addDay(),
            'status' => 'assigned',
        ]);

        $this->actingAs($user);

        Livewire::test(MyHrOnboarding::class, ['personnelId' => $personnel->id])
            ->call('openDocument', $assignment->id)
            ->assertRedirect('/storage/onboarding/policy.pdf');

        $this->assertNotNull(OnboardingDocumentReceipt::query()->where('assignment_id', $assignment->id)->value('opened_at'));

        Livewire::test(MyHrOnboarding::class, ['personnelId' => $personnel->id])
            ->call('acknowledge', $assignment->id)
            ->assertDispatched('notify');

        $assignment->refresh();
        $receipt = $assignment->receipt()->first();

        $this->assertSame('acknowledged', $assignment->status);
        $this->assertNotNull($receipt?->acknowledged_at);
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
