<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceCycle;
use App\Models\PerformanceKpi;
use App\Models\PerformanceScorecard;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiLibraryService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiTemplateService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\KpiLibraryWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\ScorecardsWorkspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KpiScorecardTest extends TestCase
{
    use RefreshDatabase;

    private Position $position;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->position = Position::query()->create(['name' => 'Satış meneceri']);
        $this->hr = User::factory()->create();
        $this->hr->givePermissionTo(Permission::findOrCreate('manage-performance-evaluation', 'web'));
        $this->actingAs($this->hr);
    }

    public function test_template_rejects_bad_weights_threshold_order_and_a_taken_position(): void
    {
        $kpi = $this->kpi('SALES');
        $service = app(KpiTemplateService::class);

        $this->assertValidationKeys(['items'], fn () => $service->save($this->templateData(), [$this->item($kpi, 60)], [$this->position->id]));
        $this->assertValidationKeys(['shares'], fn () => $service->save($this->templateData(70, 20), [$this->item($kpi, 100)], []));
        $this->assertValidationKeys(['items.0'], fn () => $service->save($this->templateData(), [$this->item($kpi, 100, threshold: 100)], []));

        $result = $service->save($this->templateData(), [$this->item($kpi, 100)], [$this->position->id]);
        $this->assertNotEmpty($result['warnings']); // 1 KPI at 100% breaks both soft rules

        $this->assertValidationKeys(['positions'], fn () => $service->save($this->templateData(), [$this->item($kpi, 100)], [$this->position->id]));
    }

    /**
     * Spec §6.4 end to end: four manual actuals give a KPI score of 81.5; closing
     * freezes the card and later actuals are refused.
     */
    public function test_scorecard_runs_the_phase_one_workflow_and_scores_the_spec_example(): void
    {
        $card = $this->specExampleCard();
        $service = app(ScorecardService::class);

        $this->assertSame('draft', $card->status);
        $this->assertCount(4, $card->items);
        $this->assertNotNull($card->items->first()->performance_kpi_version_id);

        $this->assertValidationKeys(['scorecard'], fn () => $service->transition($card, 'close', $this->hr));

        $service->transition($card, 'activate', $this->hr);
        $this->assertValidationKeys(['target'], fn () => $service->updateTarget($card->items->first(), 1, $this->hr));

        foreach ([110000, 18, 71, 100] as $index => $value) {
            $service->recordActual($card->items[$index], $value, $this->hr);
        }

        $card->refresh();
        $this->assertSame('81.5000', $card->kpi_score);
        $this->assertSame('81.5000', $card->final_score); // KPI share 100% in phase 1
        $this->assertSame('partially_meeting', $card->rating_category);

        $service->transition($card, 'submit', $this->hr);
        $service->transition($card, 'close', $this->hr);

        $card->refresh();
        $this->assertSame('closed', $card->status);
        $this->assertNotNull($card->locked_at);
        $this->assertCount(4, $card->snapshot['items']);
        $this->assertValidationKeys(['actual'], fn () => $service->recordActual($card->items->first(), 1, $this->hr));
    }

    public function test_employee_actuals_wait_for_the_manager_and_evidence_is_enforced(): void
    {
        Storage::fake('local');
        $card = $this->specExampleCard();
        $service = app(ScorecardService::class);
        $service->transition($card, 'activate', $this->hr);

        [$employee, $manager] = [$this->userFor($card->personnel), $this->userFor($this->person('Rəhbər'))];
        $card->update(['manager_personnel_id' => UserPersonnelLink::query()->where('user_id', $manager->id)->value('personnel_id')]);
        $card->refresh();

        $item = $card->items->first();
        $actual = $service->recordActual($item, 110000, $employee);
        $this->assertNull($actual->approved_at);
        $this->assertNull($item->fresh()->actual);

        $service->approveActual($actual, $manager);
        $this->assertSame('110.0000', $item->fresh()->achievement);

        $this->expectException(AuthorizationException::class);
        $service->approveActual($actual, $employee);
    }

    public function test_evidence_required_kpi_refuses_a_value_without_a_file(): void
    {
        Storage::fake('local');
        $card = $this->specExampleCard();
        $service = app(ScorecardService::class);
        $service->transition($card, 'activate', $this->hr);

        $item = $card->items->first();
        $item->kpi->update(['evidence_required' => true]);
        $item->refresh();

        $this->assertValidationKeys(['evidence'], fn () => $service->recordActual($item, 1, $this->hr));

        $actual = $service->recordActual($item, 110000, $this->hr, UploadedFile::fake()->create('akt.pdf', 20));
        Storage::disk('local')->assertExists($actual->evidence_path);
    }

    public function test_a_new_kpi_version_leaves_open_cards_on_the_old_one(): void
    {
        $card = $this->specExampleCard();
        $item = $card->items->first();
        $service = app(ScorecardService::class);
        $service->transition($card, 'activate', $this->hr);

        app(KpiLibraryService::class)->save(['direction' => 'lower_better'], $item->kpi);
        $this->assertSame(2, $item->kpi->fresh()->current_version);

        $service->recordActual($item, 110000, $this->hr);
        $this->assertSame('110.0000', $item->fresh()->achievement); // still higher_better
    }

    public function test_only_the_owner_and_their_manager_see_a_card_and_used_kpis_are_not_deleted(): void
    {
        $card = $this->specExampleCard();
        $service = app(ScorecardService::class);
        $employee = $this->userFor($card->personnel);
        $stranger = $this->userFor($this->person('Kənar'));

        $this->assertSame([$card->id], $service->visibleQuery($employee)->pluck('id')->all());
        $this->assertSame([], $service->visibleQuery($stranger)->pluck('id')->all());
        $this->assertSame('employee', $service->roleFor($employee, $card));

        $this->assertValidationKeys(['kpi'], fn () => app(KpiLibraryService::class)->delete($card->items->first()->kpi));
    }

    public function test_workspaces_render_and_drive_the_library_and_a_card(): void
    {
        $this->hr->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));

        Livewire::test(KpiLibraryWorkspace::class)
            ->call('openKpiForm')
            ->set('kpiForm.code', 'NPS')
            ->set('kpiForm.name', 'Müştəri məmnuniyyəti')
            ->call('saveKpi')
            ->assertHasNoErrors()
            ->set('section', 'templates')
            ->assertOk();

        $this->assertDatabaseHas('performance_kpi_versions', ['version' => 1]);

        $card = $this->specExampleCard();

        // The checklist shows a position owned by another template as taken, but not for that template's own form.
        $templateId = $card->performance_kpi_template_id;
        $taken = collect(Livewire::test(KpiLibraryWorkspace::class)->get('positionOptions'))->firstWhere('id', $this->position->id);
        $this->assertSame('Satış', $taken['taken']);
        $own = collect(Livewire::test(KpiLibraryWorkspace::class)->call('openTemplateForm', $templateId)->assertSet('templatePositionIds', [$this->position->id])->get('positionOptions'))->firstWhere('id', $this->position->id);
        $this->assertNull($own['taken']);

        Livewire::test(ScorecardsWorkspace::class)
            ->set('cycleId', $card->performance_cycle_id)
            ->assertSee($card->personnel->surname)
            ->call('openCard', $card->id)
            ->call('moveCard', 'activate')
            ->call('startActual', $card->items->first()->id)
            ->set('actualValue', 110000)
            ->call('saveActual')
            ->assertHasNoErrors()
            ->call('moveCard', 'close')
            ->assertHasErrors('scorecard');

        $this->assertSame('110.0000', $card->items->first()->fresh()->achievement);
    }

    private function specExampleCard(): PerformanceScorecard
    {
        $rows = [['SALES', 40, 100000], ['CLIENTS', 25, 20], ['DEBT', 20, 95], ['CRM', 15, 100]];
        $items = array_map(fn (array $row): array => $this->item($this->kpi($row[0]), $row[1], target: $row[2]), $rows);

        app(KpiTemplateService::class)->save($this->templateData(), $items, [$this->position->id]);

        $person = $this->person('Əliyev');
        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Q1', 'cycle_type' => 'quarterly', 'period_start' => '2026-01-01', 'period_end' => '2026-03-31', 'status' => 'active',
        ]);

        $this->assertSame(1, app(ScorecardService::class)->generateForCycle($cycle));
        $this->assertSame(0, app(ScorecardService::class)->generateForCycle($cycle));

        return PerformanceScorecard::query()->where('personnel_id', $person->id)->with('items.kpi')->firstOrFail();
    }

    private function kpi(string $code): PerformanceKpi
    {
        return app(KpiLibraryService::class)->save([
            'code' => $code, 'name' => $code, 'type' => 'quantitative', 'direction' => 'higher_better',
            'unit' => 'count', 'frequency' => 'quarterly', 'aggregation' => 'last', 'perspective' => 'financial', 'status' => 'active',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function templateData(float $kpiShare = 100, float $competencyShare = 0): array
    {
        return ['name' => 'Satış', 'period_type' => 'quarterly', 'kpi_weight_share' => $kpiShare, 'competency_weight_share' => $competencyShare, 'status' => 'active'];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(PerformanceKpi $kpi, float $weight, float $target = 100, float $threshold = 80): array
    {
        return ['performance_kpi_id' => $kpi->id, 'weight' => $weight, 'target' => $target, 'threshold' => $threshold, 'cap' => 120, 'target_editable' => false];
    }

    private function person(string $surname): Personnel
    {
        $structure = Structure::query()->create(['name' => 'Şöbə '.Str::random(4), 'shortname' => 'S'.Str::upper(Str::random(3))]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname, 'name' => 'Ad', 'patronymic' => 'Ata',
            'birthdate' => '1985-01-01', 'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com', 'mobile' => '994500000000', 'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
            'structure_id' => $structure->id, 'position_id' => $this->position->id,
            'join_work_date' => '2015-01-01', 'added_by' => 1, 'is_pending' => false,
        ]));
    }

    private function userFor(Personnel $personnel): User
    {
        $user = User::factory()->create();
        UserPersonnelLink::query()->create(['user_id' => $user->id, 'personnel_id' => $personnel->id]);

        return $user;
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function assertValidationKeys(array $keys, callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $this->assertSame($keys, array_keys($exception->errors()));

            return;
        }

        $this->fail('Expected a validation error on '.implode(', ', $keys));
    }
}
