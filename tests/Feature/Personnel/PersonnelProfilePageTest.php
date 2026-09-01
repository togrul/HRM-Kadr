<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Application\Services\PersonnelProfileReadService;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\PersonnelProfile;
use App\Modules\Personnel\Livewire\PersonnelQuickView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PersonnelProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_the_view_permission(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAs(User::factory()->create());

        Livewire::test(PersonnelProfile::class, ['personnel' => $personnel])->assertForbidden();
    }

    public function test_overview_renders_for_a_read_only_viewer(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsViewer();

        Livewire::test(PersonnelProfile::class, ['personnel' => $personnel])
            ->assertOk()
            ->assertSet('section', 'overview');
    }

    public function test_editable_sections_are_refused_without_edit_permission(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsViewer();

        foreach (array_keys(PersonnelProfile::SECTION_STEPS) as $section) {
            Livewire::test(PersonnelProfile::class, ['personnel' => $personnel])
                ->call('setSection', $section)
                ->assertSet('section', 'overview');
        }
    }

    public function test_a_deep_linked_editable_section_falls_back_for_a_read_only_viewer(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsViewer();

        Livewire::withUrlParams(['section' => 'military'])
            ->test(PersonnelProfile::class, ['personnel' => $personnel])
            ->assertSet('section', 'overview');
    }

    public function test_opening_an_editable_section_from_the_overview_switches_directly(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsEditor();

        Livewire::test(PersonnelProfile::class, ['personnel' => $personnel])
            ->call('setSection', 'career')
            ->assertSet('section', 'career')
            ->assertNotDispatched('personnel-profile:goto-step');
    }

    public function test_moving_between_editable_sections_asks_the_wizard_first(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsEditor();

        // The wizard owns unsaved edits, so the panel requests the move and waits for the
        // step the wizard actually lands on rather than switching optimistically.
        Livewire::withUrlParams(['section' => 'personal'])
            ->test(PersonnelProfile::class, ['personnel' => $personnel])
            ->call('setSection', 'kinship')
            ->assertDispatched('personnel-profile:goto-step', targetStep: 7)
            ->assertSet('section', 'personal');
    }

    public function test_the_panel_follows_the_step_the_wizard_reports(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsEditor();

        Livewire::withUrlParams(['section' => 'personal'])
            ->test(PersonnelProfile::class, ['personnel' => $personnel])
            ->call('syncSectionFromWizard', 5)
            ->assertSet('section', 'military');
    }

    public function test_sections_and_wizard_steps_are_one_to_one(): void
    {
        $steps = PersonnelProfile::SECTION_STEPS;
        $reader = app(PersonnelProfileReadService::class);

        $this->assertSame(range(1, 8), array_values($steps), 'every wizard step needs a section');
        $this->assertSame(
            ['overview', ...array_keys($steps)],
            $reader->sectionKeys(),
            'the panel must list the overview plus each wizard step, in order'
        );
    }

    public function test_an_unknown_section_falls_back_to_the_overview(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsViewer();

        Livewire::withUrlParams(['section' => 'nonsense'])
            ->test(PersonnelProfile::class, ['personnel' => $personnel])
            ->assertOk()
            ->assertSet('section', 'overview');

        // setSection must reject the same way the URL does.
        Livewire::test(PersonnelProfile::class, ['personnel' => $personnel])
            ->call('setSection', 'nonsense')
            ->assertSet('section', 'overview');
    }

    public function test_section_counts_report_the_records_behind_each_tab(): void
    {
        $personnel = $this->seedPersonnel();
        DB::table('personnel_cards')->insert([
            ['tabel_no' => $personnel->tabel_no, 'card_number' => 'C-1', 'valid_date' => '2030-01-01'],
            ['tabel_no' => $personnel->tabel_no, 'card_number' => 'C-2', 'valid_date' => '2031-01-01'],
        ]);

        $reader = app(PersonnelProfileReadService::class);
        $counts = $reader->sectionCounts($reader->load($personnel->fresh()));

        $this->assertSame(2, $counts['documents']);
        $this->assertSame(0, $counts['kinship']);
        $this->assertNull($counts['overview']);
    }

    public function test_an_editable_section_does_not_hydrate_every_relation(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsEditor();

        $reader = app(PersonnelProfileReadService::class);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $loaded = $reader->load($personnel->fresh(), 'military');
        $reader->sectionCounts($loaded);
        $reader->structurePath($loaded);

        // position + structure chain + one counts subselect. The wizard fetches the step's
        // own records, so loading all ~35 section relations here was pure waste.
        $this->assertLessThanOrEqual(6, $queries, "profile read used {$queries} queries");
    }

    public function test_tenure_is_reported_in_whole_years_and_months(): void
    {
        $personnel = $this->seedPersonnel(['join_work_date' => today()->subYears(2)->subMonths(10)->toDateString()]);

        $reader = app(PersonnelProfileReadService::class);
        $tenure = collect($reader->identityMeta($reader->load($personnel)))->last()['value'];

        $this->assertSame(__('personnel::profile.labels.years', ['count' => 2])
            .' '.__('personnel::profile.labels.months', ['count' => 10]), $tenure);
    }

    public function test_row_action_opens_the_quick_view_panel(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsViewer();

        Livewire::test(AllPersonnel::class)
            ->call('handleRowAction', 'quick-view', ['type' => 'quick-view', 'value' => $personnel->tabel_no])
            ->assertSet('showSideMenu', 'quick-view')
            ->assertSet('modelName', $personnel->tabel_no);
    }

    public function test_quick_view_requires_the_view_permission(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAs(User::factory()->create());

        Livewire::test(PersonnelQuickView::class, ['personnelModel' => $personnel->tabel_no])
            ->assertForbidden();
    }

    public function test_a_blank_tabel_number_opens_nothing(): void
    {
        $this->seedPersonnel();
        $this->actingAsViewer();

        Livewire::test(AllPersonnel::class)
            ->call('handleRowAction', 'quick-view', ['type' => 'quick-view', 'value' => ''])
            ->assertSet('showSideMenu', '');
    }

    public function test_quick_view_renders_the_summary_and_links_to_the_profile(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsViewer();

        Livewire::test(PersonnelQuickView::class, ['personnelModel' => $personnel->tabel_no])
            ->assertOk()
            ->assertSee($personnel->fullname)
            ->assertSee(route('personnel.show', $personnel->id), escape: false);
    }

    public function test_section_nav_is_rendered_inside_the_component_so_its_clicks_bind(): void
    {
        $personnel = $this->seedPersonnel();
        $this->actingAsViewer();

        // A sidebar *slot* is rendered by the layout, outside the Livewire root, so its
        // wire:click never binds. Teleported panel markup shows up in the component's
        // own output — that is what this asserts.
        Livewire::test(PersonnelProfile::class, ['personnel' => $personnel])
            ->assertSee(__('personnel::profile.sections.documents'))
            ->assertSee('setSection', escape: false);
    }

    private function actingAsViewer(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-personnels', 'web');
        $user->givePermissionTo('show-personnels');
        $this->actingAs($user);
    }

    private function actingAsEditor(): void
    {
        $user = User::factory()->create();

        foreach (['show-personnels', 'edit-personnels'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo(['show-personnels', 'edit-personnels']);
        $this->actingAs($user);
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function seedPersonnel(array $overrides = []): Personnel
    {
        DB::table('personnels')->insert(array_merge([
            'tabel_no' => 'T-900',
            'surname' => 'Məmmədov',
            'name' => 'Elçin',
            'patronymic' => 'Rasim oğlu',
            'birthdate' => '1986-07-14',
            'mobile' => '0500000001',
            'nationality_id' => 1,
            'pin' => 'PIN-900',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'join_work_date' => '2019-03-12',
            'added_by' => 1,
            'work_norm_id' => 1,
            'is_pending' => 0,
        ], $overrides));

        return Personnel::where('tabel_no', 'T-900')->firstOrFail();
    }
}
