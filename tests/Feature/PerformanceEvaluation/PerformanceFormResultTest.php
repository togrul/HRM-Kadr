<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceCycle;
use App\Models\PerformanceForm;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceWeakAreaTrainingNeedService;
use App\Modules\PerformanceEvaluation\Livewire\EvaluatorScoreCapture;
use App\Modules\PerformanceEvaluation\Livewire\FoundationWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\OperationsWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PerformanceFormResultTest extends TestCase
{
    use RefreshDatabase;

    private ?User $managerUser = null;

    public function test_self_rating_never_counts_and_manager_rating_wins_over_hr(): void
    {
        [$form, $itemA, $itemB] = $this->formWithItems();

        $this->score($form, $itemA, 'self', 100);
        $this->score($form, $itemA, 'hr', 20);
        $this->score($form, $itemA, 'manager', 50);
        $this->score($form, $itemB, 'self', 100);
        $this->score($form, $itemB, 'hr', 70);

        app(PerformanceWeakAreaTrainingNeedService::class)->refreshFormResult($form);

        // A → manager 50, B → hr 70 (no manager score); equal item weights.
        $this->assertSame('60.00', (string) $form->fresh()->final_score);
        $this->assertSame('medium', $form->fresh()->final_category);
    }

    public function test_only_self_ratings_leave_the_form_without_a_final_score(): void
    {
        [$form, $itemA] = $this->formWithItems();

        $this->score($form, $itemA, 'self', 95);

        app(PerformanceWeakAreaTrainingNeedService::class)->refreshFormResult($form);

        $this->assertNull($form->fresh()->final_score);
        $this->assertNull($form->fresh()->final_category);
    }

    public function test_section_weights_combine_per_section_averages(): void
    {
        [$form, $itemA, $itemB, $itemC] = $this->formWithItems(sectionWeights: [70, 30]);

        // Section 1 (70%): A=90 w30, B=60 w10 → 82.5. Section 2 (30%): C=40 → 40.
        $this->score($form, $itemA, 'manager', 90);
        $this->score($form, $itemB, 'manager', 60);
        $this->score($form, $itemC, 'manager', 40);

        app(PerformanceWeakAreaTrainingNeedService::class)->refreshFormResult($form);

        // 82.5 × 0.7 + 40 × 0.3 = 69.75
        $this->assertSame('69.75', (string) $form->fresh()->final_score);
    }

    public function test_closed_cycle_blocks_score_capture_form_changes_and_cycle_deletion(): void
    {
        [$form, $itemA] = $this->formWithItems();
        $form->cycle->update(['status' => 'closed']);
        $this->actingAs($this->manager());

        Livewire::test(EvaluatorScoreCapture::class)
            ->set('scoreForm.performance_form_id', $form->id)
            ->set('scoreForm.performance_form_template_item_id', $itemA->id)
            ->set('scoreForm.evaluator_type', 'manager')
            ->set('scoreForm.score', 80)
            ->call('saveAssignedScore')
            ->assertHasErrors('scoreForm.score');

        Livewire::test(OperationsWorkspace::class, ['tab' => 'evaluations'])
            ->set('scoreForm.performance_form_id', $form->id)
            ->set('scoreForm.performance_form_template_item_id', $itemA->id)
            ->set('scoreForm.evaluator_type', 'manager')
            ->set('scoreForm.score', 80)
            ->call('storeScore')
            ->assertHasErrors('scoreForm.score');

        Livewire::test(OperationsWorkspace::class, ['tab' => 'evaluations'])
            ->call('deleteEvaluationForm', $form->id)
            ->assertHasErrors('evaluationForm.performance_cycle_id');

        Livewire::test(FoundationWorkspace::class, ['tab' => 'cycles'])
            ->call('deleteCycle', $form->performance_cycle_id)
            ->assertHasErrors('cycleForm.status');

        $this->assertDatabaseCount('performance_form_scores', 0);
        $this->assertDatabaseHas('performance_forms', ['id' => $form->id]);
    }

    public function test_goals_cascade_with_the_person_they_belong_to(): void
    {
        $foreignKey = collect(Schema::getForeignKeys('performance_goals'))
            ->first(fn (array $key): bool => $key['columns'] === ['personnel_id']);

        $this->assertSame('personnels', $foreignKey['foreign_table'] ?? null);
        $this->assertSame('cascade', strtolower((string) ($foreignKey['on_delete'] ?? '')));
    }

    public function test_form_builder_creates_a_form_its_sections_and_criteria_in_place(): void
    {
        $this->actingAs($this->manager());
        $group = \App\Models\TrainingCompetencyGroup::query()->create(['name' => 'Core', 'slug' => 'core']);
        $competency = \App\Models\TrainingCompetency::query()->create(['training_competency_group_id' => $group->id, 'name' => 'Komanda işi', 'slug' => 'komanda-isi', 'is_active' => true]);

        $builder = Livewire::test(FoundationWorkspace::class, ['tab' => 'templates'])
            ->call('newTemplate')
            ->set('templateForm.name', 'Davranış forması')
            ->call('saveBuilderTemplate')
            ->assertHasNoErrors()
            ->assertSet('showSideMenu', '');

        $template = PerformanceFormTemplate::query()->where('name', 'Davranış forması')->firstOrFail();
        $builder->assertSet('builderTemplateId', $template->id)
            ->call('newSection', $template->id)
            ->set('sectionForm.name', 'Əməkdaşlıq')
            ->set('sectionForm.weight_percent', 100)
            ->call('saveBuilderSection')
            ->assertHasNoErrors();

        $section = $template->sections()->firstOrFail();
        $builder->call('newItem', $section->id)
            ->assertSet('itemForm.performance_form_template_section_id', $section->id)
            ->set('itemForm.name', 'Komandada işləyir')
            ->set('itemForm.training_competency_id', $competency->id)
            ->call('saveBuilderItem')
            ->assertHasNoErrors()
            ->assertSee('Əməkdaşlıq')
            ->assertSee('Komandada işləyir');

        $this->assertDatabaseHas('performance_form_template_items', ['performance_form_template_section_id' => $section->id, 'name' => 'Komandada işləyir']);
    }

    public function test_cycles_and_evaluations_work_from_side_panels(): void
    {
        $this->actingAs($this->manager());

        Livewire::test(FoundationWorkspace::class, ['tab' => 'cycles'])
            ->call('newCycle')
            ->assertSet('showSideMenu', 'form-cycle')
            ->set('cycleForm.name', '2027 illik')
            ->set('cycleForm.period_start', '2027-01-01')
            ->set('cycleForm.period_end', '2027-12-31')
            ->call('saveBuilderCycle')
            ->assertHasNoErrors()
            ->assertSet('showSideMenu', '')
            ->assertSee('2027 illik');

        [$form, $itemA] = $this->formWithItems();

        Livewire::test(OperationsWorkspace::class, ['tab' => 'evaluations'])
            ->dispatch('performance-evaluation:score-form', formId: $form->id)
            ->assertSet('showSideMenu', 'form-score')
            ->assertSet('scoreForm.performance_form_id', $form->id)
            ->set('scoreForm.performance_form_template_item_id', $itemA->id)
            ->set('scoreForm.evaluator_type', 'manager')
            ->set('scoreForm.score', 70)
            ->call('saveScore')
            ->assertHasNoErrors()
            ->assertSet('showSideMenu', '');

        $this->assertSame('70.00', (string) $form->fresh()->final_score);

        Livewire::test(\App\Modules\PerformanceEvaluation\Livewire\EvaluationsSummary::class)
            ->assertSee('Soyad')
            ->set('formSearch', 'Yoxdur')
            ->assertDontSee('Soyad');
    }

    /**
     * @param  array<int, float>  $sectionWeights
     * @return array{0: PerformanceForm, 1: PerformanceFormTemplateItem, 2: PerformanceFormTemplateItem, 3: PerformanceFormTemplateItem}
     */
    private function formWithItems(array $sectionWeights = [0, 0]): array
    {
        $template = PerformanceFormTemplate::query()->create(['name' => 'İllik forma']);
        $first = PerformanceFormTemplateSection::query()->create([
            'performance_form_template_id' => $template->id, 'name' => 'Nəticələr', 'weight_percent' => $sectionWeights[0],
        ]);
        $second = PerformanceFormTemplateSection::query()->create([
            'performance_form_template_id' => $template->id, 'name' => 'Davranış', 'weight_percent' => $sectionWeights[1],
        ]);

        $itemA = $this->item($first, 'A', $sectionWeights[0] > 0 ? 30 : 0);
        $itemB = $this->item($first, 'B', $sectionWeights[0] > 0 ? 10 : 0);
        $itemC = $this->item($second, 'C', 0);

        $form = PerformanceForm::query()->create([
            'performance_cycle_id' => $this->cycle()->id,
            'performance_form_template_id' => $template->id,
            'personnel_id' => $this->makePersonnel()->id,
            'manager_id' => $this->manager()->id,
        ]);

        return [$form, $itemA, $itemB, $itemC];
    }

    private function item(PerformanceFormTemplateSection $section, string $name, float $weight): PerformanceFormTemplateItem
    {
        return PerformanceFormTemplateItem::query()->create([
            'performance_form_template_section_id' => $section->id, 'name' => $name, 'weight_percent' => $weight,
        ]);
    }

    private function score(PerformanceForm $form, PerformanceFormTemplateItem $item, string $type, float $score): void
    {
        $form->scores()->create([
            'performance_form_template_item_id' => $item->id, 'evaluator_type' => $type, 'score' => $score,
        ]);
    }

    private function cycle(): PerformanceCycle
    {
        return PerformanceCycle::query()->create([
            'name' => 'Dövr '.Str::random(4), 'cycle_type' => 'annual',
            'period_start' => '2026-01-01', 'period_end' => '2026-12-31', 'status' => 'active',
        ]);
    }

    private function manager(): User
    {
        if ($this->managerUser !== null) {
            return $this->managerUser;
        }

        $this->managerUser = User::factory()->create();
        foreach (['show-performance-evaluation', 'manage-performance-evaluation', 'review-performance-evaluation'] as $permission) {
            $this->managerUser->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $this->managerUser;
    }

    private function makePersonnel(): Personnel
    {
        $structure = Structure::query()->create(['name' => 'Şöbə '.Str::random(4), 'shortname' => 'S'.Str::upper(Str::random(3))]);
        $position = Position::query()->create(['name' => 'Vəzifə '.Str::random(4)]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Soyad', 'name' => 'Ad', 'patronymic' => 'Ata',
            'birthdate' => '1985-01-01', 'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com', 'mobile' => '994500000000', 'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
            'structure_id' => $structure->id, 'position_id' => $position->id,
            'join_work_date' => '2015-01-01', 'added_by' => 1, 'is_pending' => false,
        ]));
    }
}
