<?php

namespace Tests\Feature\Services;

use App\Models\AppealStatus;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CandidateSettingsSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_section_does_not_render_candidate_presets_panel(): void
    {
        Livewire::test(\App\Modules\Services\Livewire\Settings\SettingsList::class, ['section' => 'general'])
            ->assertDontSee(__('services::settings.labels.candidate_status_whitelist_presets'))
            ->assertDontSee(__('services::settings.actions.save_presets'));
    }

    public function test_general_section_renders_chief_governance_panel(): void
    {
        Livewire::test(\App\Modules\Services\Livewire\Settings\SettingsList::class, ['section' => 'general'])
            ->assertSee('Rəhbər və Həvalə')
            ->assertSee('Daimi rəhbər')
            ->assertSee('Müvəqqəti həvalə');
    }

    public function test_it_creates_chief_delegation_from_settings_panel(): void
    {
        $this->seedChiefReferenceData();

        $chief = $this->makePersonnel('CH-001', 'Rəhbər', 'Daimi', positionId: 2);
        $delegate = $this->makePersonnel('DL-001', 'Əvəz edən', 'Müvəqqəti', positionId: 1);

        Livewire::test(\App\Modules\Services\Livewire\Settings\SettingsList::class, ['section' => 'general'])
            ->set('chiefPersonnelId', $chief->id)
            ->call('saveChiefPersonnel')
            ->set('chiefDelegationForm.delegate_personnel_id', $delegate->id)
            ->set('chiefDelegationForm.starts_at', '2026-06-13')
            ->set('chiefDelegationForm.ends_at', '2026-06-20')
            ->set('chiefDelegationForm.reason', 'Məzuniyyət')
            ->set('chiefDelegationForm.basis_document', 'Əmr 12')
            ->call('createChiefDelegation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('chief_delegations', [
            'chief_personnel_id' => $chief->id,
            'delegate_personnel_id' => $delegate->id,
            'reason' => 'Məzuniyyət',
            'basis_document' => 'Əmr 12',
            'is_active' => true,
        ]);
    }

    public function test_chief_delegation_form_shows_validation_errors(): void
    {
        Livewire::test(\App\Modules\Services\Livewire\Settings\SettingsList::class, ['section' => 'general'])
            ->call('createChiefDelegation')
            ->assertHasErrors([
                'chiefDelegationForm.delegate_personnel_id',
                'chiefDelegationForm.starts_at',
            ])
            ->assertSee('Vəzifəni icra edən əməkdaş')
            ->assertSee('Başlama tarixi');
    }

    public function test_candidate_section_renders_candidate_preset_panel(): void
    {
        AppealStatus::query()->create([
            'id' => 10,
            'locale' => app()->getLocale(),
            'name' => 'Baxılır',
        ]);

        Livewire::test(\App\Modules\Services\Livewire\Settings\SettingsList::class, ['section' => 'candidate'])
            ->assertSee(__('services::settings.labels.candidate_status_whitelist_presets'))
            ->assertSee(__('services::settings.actions.save_presets'))
            ->assertSee('Baxılır');
    }

    private function seedChiefReferenceData(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore([
            'id' => 1,
            'title_az' => 'Ali',
            'title_en' => 'Higher',
            'title_ru' => 'Higher',
        ]);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 1,
            'name' => 'Baş ofis',
            'shortname' => 'Baş ofis',
            'parent_id' => null,
            'code' => 1,
            'level' => 1,
            'coefficient' => 1,
        ]);
        DB::table('positions')->insertOrIgnore([
            ['id' => 1, 'name' => 'Şöbə müdiri', 'approval_rank' => 20, 'is_approval_target' => true],
            ['id' => 2, 'name' => 'Baş direktor', 'approval_rank' => 100, 'is_approval_target' => true],
        ]);
    }

    private function makePersonnel(string $tabelNo, string $surname, string $name, int $positionId): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => $tabelNo,
            'surname' => $surname,
            'name' => $name,
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '0500000000',
            'nationality_id' => 1,
            'pin' => $tabelNo,
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => $positionId,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => User::factory()->create()->id,
        ]));
    }
}
