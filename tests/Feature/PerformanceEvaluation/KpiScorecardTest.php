<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceCycle;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Models\PerformanceGoal;
use App\Models\PerformanceKpi;
use App\Models\PerformanceScorecard;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiLibraryService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiTemplateService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardLifecycleService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardReviewService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\KpiLibraryWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\ScorecardsWorkspace;
use App\Notifications\PlatformNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
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

        foreach (['start_self_review', 'submit_self_review', 'submit_manager_review', 'approve', 'close'] as $action) {
            $service->transition($card, $action, $this->hr);
        }

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

    public function test_agreement_round_trip_needs_a_reason_to_object_and_notifies_the_other_side(): void
    {
        Notification::fake();
        $card = $this->specExampleCard();
        [$employee, $manager] = $this->employeeAndManager($card);
        $service = app(ScorecardService::class);

        $this->assertSame(['send_for_agreement', 'activate'], $service->availableActions($card, $this->hr));
        $service->transition($card, 'send_for_agreement', $manager);
        $this->assertSame('pending_agreement', $card->fresh()->status);
        $this->assertNotNull($card->fresh()->stage_due_at);
        Notification::assertSentTo($employee, PlatformNotification::class);

        $this->assertValidationKeys(['reason'], fn () => $service->transition($card->fresh(), 'reject', $employee, '  '));
        $service->transition($card->fresh(), 'reject', $employee, 'Satış planı real deyil');
        $this->assertSame('draft', $card->fresh()->status);
        $this->assertSame('Satış planı real deyil', $card->events()->first()->reason);
        Notification::assertSentTo($manager, PlatformNotification::class);

        $service->transition($card->fresh(), 'send_for_agreement', $manager);
        $service->transition($card->fresh(), 'accept', $employee);
        $this->assertSame('active', $card->fresh()->status);

        $this->expectException(AuthorizationException::class);
        $service->transition($card->fresh(), 'start_self_review', $employee);
    }

    /**
     * Spec §6.4 with the competency block: KPI 81.5 × 0.7 + competency 100 × 0.3 = 87.05,
     * where only the manager's rating reaches the score; HR then calibrates it.
     */
    public function test_competency_block_self_and_manager_review_then_calibration(): void
    {
        $formTemplate = PerformanceFormTemplate::query()->create(['name' => 'Kompetensiyalar']);
        $section = PerformanceFormTemplateSection::query()->create(['performance_form_template_id' => $formTemplate->id, 'name' => 'Davranış']);
        $competency = PerformanceFormTemplateItem::query()->create(['performance_form_template_section_id' => $section->id, 'name' => 'Komanda işi']);

        $card = $this->specExampleCard($formTemplate->id);
        $this->assertNotNull($card->performance_form_id);
        [$employee, $manager] = $this->employeeAndManager($card);
        $scorecards = app(ScorecardService::class);
        $review = app(ScorecardReviewService::class);

        $scorecards->transition($card, 'activate', $this->hr);
        foreach ([110000, 18, 71, 100] as $index => $value) {
            $scorecards->recordActual($card->items[$index], $value, $manager);
        }
        $this->assertNull($card->fresh()->final_score); // competency block still empty

        $scorecards->transition($card->fresh(), 'start_self_review', $manager);
        $review->rateCompetency($card->fresh(), $competency->id, 'self', 5, null, $employee);
        $this->assertNull($card->fresh()->competency_score); // a self rating never counts
        $this->assertSame(5, $review->competencies($card->fresh())->first()['self']);

        $scorecards->transition($card->fresh(), 'submit_self_review', $employee);
        $this->assertValidationKeys(['scorecard'], fn () => $scorecards->transition($card->fresh(), 'submit_manager_review', $manager));

        $review->rateCompetency($card->fresh(), $competency->id, 'manager', 3, null, $manager);
        $this->assertSame('100.0000', $card->fresh()->competency_score);
        $this->assertSame('87.0500', $card->fresh()->final_score);

        $scorecards->transition($card->fresh(), 'submit_manager_review', $manager);
        $this->assertValidationKeys(['calibration'], fn () => $review->calibrate($card->fresh(), 20, 'Çox', $this->hr));
        $review->calibrate($card->fresh(), 5, 'Bölmə üzrə uyğunlaşdırma', $this->hr);

        $card->refresh();
        $this->assertSame('87.0500', $card->final_score);
        $this->assertSame('92.0500', $card->calibrated_score);
        $this->assertSame('meeting', $card->rating_category);
        $this->assertSame(1, $review->distribution($card->performance_cycle_id)['meeting']['count']);

        $scorecards->transition($card, 'approve', $this->hr);
        $scorecards->transition($card->fresh(), 'close', $this->hr);
        $this->assertSame('92.0500', $card->fresh()->snapshot['calibrated_score']);
    }

    public function test_deadlines_auto_accept_remind_and_escalate(): void
    {
        Notification::fake();
        $card = $this->specExampleCard();
        [$employee, $manager] = $this->employeeAndManager($card);
        $scorecards = app(ScorecardService::class);
        $lifecycle = app(ScorecardLifecycleService::class);

        $scorecards->transition($card, 'send_for_agreement', $manager);
        $card->update(['stage_due_at' => today()->subDay()]);
        $this->assertSame(1, $lifecycle->runDeadlines()['auto_accepted']);
        $this->assertSame('active', $card->fresh()->status);
        $this->assertSame('accept', $card->events()->first()->action);

        $scorecards->transition($card->fresh(), 'start_self_review', $manager);
        $card->update(['stage_due_at' => today()->addWeekday()]);
        $this->assertSame(['auto_accepted' => 0, 'reminded' => 1, 'escalated' => 0], $lifecycle->runDeadlines());
        $this->assertSame(0, $lifecycle->runDeadlines()['reminded']); // once per stage

        $card->update(['stage_due_at' => today()->subWeekdays(2)]);
        $this->assertSame(1, $lifecycle->runDeadlines()['escalated']);
        Notification::assertSentTo($manager, PlatformNotification::class); // the employee is late → their manager hears
    }

    public function test_position_change_splits_the_cycle_and_leaving_closes_the_card(): void
    {
        $card = $this->specExampleCard();
        $scorecards = app(ScorecardService::class);
        $scorecards->transition($card, 'activate', $this->hr);
        $scorecards->recordActual($card->items[0], 100000, $this->hr);

        $newPosition = Position::query()->create(['name' => 'Satış rəhbəri']);
        app(KpiTemplateService::class)->save(
            [...$this->templateData(), 'name' => 'Rəhbər'],
            [$this->item($this->kpi('TEAM'), 100)],
            [$newPosition->id],
        );
        $card->personnel->forceFill(['position_id' => $newPosition->id])->saveQuietly();

        $result = app(ScorecardLifecycleService::class)->syncPersonnel(Carbon::parse('2026-02-15'));
        $this->assertSame(['terminated' => 0, 'position_changed' => 1, 'reopened' => 1], $result);

        $card->refresh();
        $this->assertSame('closed', $card->status);
        $this->assertSame('position_changed', $card->closure_reason);
        $this->assertSame('2026-02-14', $card->valid_to->toDateString());

        $next = PerformanceScorecard::query()->where('personnel_id', $card->personnel_id)->whereKeyNot($card->id)->firstOrFail();
        $this->assertSame('2026-02-15', $next->valid_from->toDateString());
        $scorecards->transition($next, 'activate', $this->hr);
        $scorecards->recordActual($next->items()->first(), 50, $this->hr);

        // 45 days at 40% (card 1: only SALES at 100%) + 45 days at 0% (card 2: 50% is under threshold)
        $this->assertSame(20.0, $scorecards->personCycleScore($card->personnel_id, $card->performance_cycle_id));

        $next->personnel->forceFill(['leave_work_date' => '2026-03-10'])->saveQuietly();
        $this->assertSame(1, app(ScorecardLifecycleService::class)->syncPersonnel(Carbon::parse('2026-03-11'))['terminated']);
        $this->assertSame('terminated', $next->fresh()->closure_reason);
    }

    public function test_checkins_while_active_and_goal_links_while_draft(): void
    {
        $card = $this->specExampleCard();
        $review = app(ScorecardReviewService::class);
        $goal = PerformanceGoal::query()->create(['performance_cycle_id' => $card->performance_cycle_id, 'title' => 'Gəlir +20%']);

        $this->assertSame(4, $review->unlinkedItemCount($card->performance_cycle_id));
        $review->linkGoal($card->items[0], $goal->id, $this->hr);
        $this->assertSame(3, $review->unlinkedItemCount($card->performance_cycle_id));

        $this->assertValidationKeys(['checkin'], fn () => $review->addCheckin($card, today(), 'Plan 60%', null, $this->hr));
        app(ScorecardService::class)->transition($card, 'activate', $this->hr);
        $review->addCheckin($card->fresh(), today(), 'Plan 60%', 'Debitor borc gecikir', $this->hr);

        $this->assertSame(1, $card->checkins()->count());
        $this->assertSame(3, $review->expectedCheckins($card)); // a 90-day quarter → one a month
        $this->assertValidationKeys(['goal'], fn () => $review->linkGoal($card->fresh()->items[1], $goal->id, $this->hr));
    }

    public function test_card_screen_drives_competencies_calibration_and_checkins(): void
    {
        $this->hr->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        $formTemplate = PerformanceFormTemplate::query()->create(['name' => 'Kompetensiyalar']);
        $section = PerformanceFormTemplateSection::query()->create(['performance_form_template_id' => $formTemplate->id, 'name' => 'Davranış']);
        $competency = PerformanceFormTemplateItem::query()->create(['performance_form_template_section_id' => $section->id, 'name' => 'Komanda işi']);
        $card = $this->specExampleCard($formTemplate->id);

        $screen = Livewire::test(ScorecardsWorkspace::class)
            ->set('cycleId', $card->performance_cycle_id)
            ->call('openCard', $card->id)
            ->assertSee('Komanda işi')
            ->call('moveCard', 'close')
            ->assertHasErrors('scorecard')
            ->call('moveCard', 'activate')
            ->set('checkinProgress', 'Yarı yolda')
            ->call('saveCheckin')
            ->assertHasNoErrors()
            ->assertSee('Yarı yolda');

        foreach (['start_self_review', 'submit_self_review'] as $action) {
            $screen->call('moveCard', $action)->assertHasNoErrors();
        }

        $screen->call('rate', $competency->id, 'manager', 4)
            ->call('moveCard', 'submit_manager_review')
            ->assertHasNoErrors()
            ->set('calibrationDelta', -3)
            ->set('calibrationReason', 'Bölmə ortası')
            ->call('saveCalibration')
            ->assertHasNoErrors()
            ->assertSee('Bölmə ortası');

        $this->assertSame('calibration', $card->fresh()->status);
        $this->assertSame('110.0000', $card->fresh()->competency_score);
        $this->assertNotNull($card->fresh()->calibrated_score);

        $screen->call('closeCard')->assertSee(__('performance_evaluation::kpi.sections.distribution'));
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function employeeAndManager(PerformanceScorecard $card): array
    {
        $employee = $this->userFor($card->personnel);
        $manager = $this->userFor($this->person('Rəhbər'));
        $card->update(['manager_personnel_id' => UserPersonnelLink::query()->where('user_id', $manager->id)->value('personnel_id')]);
        $card->refresh();

        return [$employee, $manager];
    }

    private function specExampleCard(?int $competencyFormTemplateId = null): PerformanceScorecard
    {
        $rows = [['SALES', 40, 100000], ['CLIENTS', 25, 20], ['DEBT', 20, 95], ['CRM', 15, 100]];
        $items = array_map(fn (array $row): array => $this->item($this->kpi($row[0]), $row[1], target: $row[2]), $rows);

        $data = $competencyFormTemplateId === null
            ? $this->templateData()
            : [...$this->templateData(70, 30), 'performance_form_template_id' => $competencyFormTemplateId];
        app(KpiTemplateService::class)->save($data, $items, [$this->position->id]);

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
