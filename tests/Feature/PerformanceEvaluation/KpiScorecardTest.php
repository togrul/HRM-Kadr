<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\AttendanceCalendar;
use App\Models\Award;
use App\Models\AwardType;
use App\Models\OrderLog;
use App\Models\PayrollOneOffEarning;
use App\Models\PerformanceBonusCalculation;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Models\PerformanceGoal;
use App\Models\PerformanceKpi;
use App\Models\PerformanceScorecard;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\Attendance\Domain\Contracts\PayrollAttendanceReadRepository;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\BonusService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\InternalKpiMetrics;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiActualsImportService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiLibraryService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiTemplateService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardLifecycleService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardReviewService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\BonusWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\KpiLibraryWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\ScorecardsWorkspace;
use App\Notifications\PlatformNotification;
use App\Services\Orders\Document\OrderStatusTransitionService;
use App\Services\Profiles\ProfileState;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\PerformanceEvaluation\Concerns\BuildsKpiCards;
use Tests\TestCase;

class KpiScorecardTest extends TestCase
{
    use BuildsKpiCards;
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
        $this->assertDatabaseHas('talent_assessments', [
            'personnel_id' => $card->personnel_id,
            'performance_cycle_id' => $card->performance_cycle_id,
            'performance_level' => 1,
            'potential_level' => 2,
        ]);
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
        $this->assertSame(['terminated' => 0, 'position_changed' => 1, 'reopened' => 1, 'manager_changed' => 0], $result);

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
        // Notice given, not yet gone: the card keeps running until the leave date passes.
        $this->assertSame(0, app(ScorecardLifecycleService::class)->syncPersonnel(Carbon::parse('2026-03-01'))['terminated']);
        $this->assertNull($next->fresh()->closure_reason);
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
    public function test_excel_import_checks_the_whole_file_before_recording_anything(): void
    {
        $card = $this->specExampleCard();
        $scorecards = app(ScorecardService::class);
        $import = app(KpiActualsImportService::class);
        $scorecards->transition($card, 'activate', $this->hr);

        $template = $import->templateRows($card->cycle);
        $this->assertCount(4, $template);
        $valueAt = array_search('value', KpiActualsImportService::COLUMNS, true);
        $sheet = [array_column($import->columns(), 'label')];
        foreach ($template as $index => $row) {
            $line = array_values($row);
            $line[$valueAt] = [110000, 18, 71, 100][$index];
            $sheet[] = $line;
        }

        $broken = $sheet;
        $broken[2][$valueAt] = 'abc';
        $broken[] = [999999, null, null, null, null, null, null, null, 5, null];
        $result = $import->import($card->cycle, $broken, $this->hr);
        $this->assertSame(0, $result['imported']);
        $this->assertSame([3, 6], array_keys($result['errors']));
        $this->assertSame(0, $card->items()->withCount('actuals')->get()->sum('actuals_count'));

        $this->assertSame(4, $import->import($card->cycle, $sheet, $this->hr)['imported']);
        $card->refresh();
        $this->assertSame('81.5000', $card->kpi_score);
        $this->assertDatabaseHas('performance_kpi_actuals', ['source' => 'import']);
    }

    public function test_scorecards_screen_uploads_actuals_from_a_file(): void
    {
        $card = $this->specExampleCard();
        app(ScorecardService::class)->transition($card, 'activate', $this->hr);
        $csv = "item_id,tabel_no,personnel,kpi_code,kpi,unit,target,actual,value,note\n"
            .$card->items->map(fn ($item, $index): string => $item->id.',,,,,,,,'.[110000, 18, 71, 100][$index].',')->implode("\n");

        Storage::fake('local');
        $this->hr->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        $screen = Livewire::test(ScorecardsWorkspace::class);
        $screen->call('toggleImport')->assertSee(__('performance_evaluation::kpi.import.title'));
        $screen->set('importFile', UploadedFile::fake()->createWithContent('actuals.csv', $csv))
            ->call('importActuals')
            ->assertHasNoErrors()
            ->assertSet('importErrors', []);

        $this->assertSame('81.5000', $card->refresh()->kpi_score);
    }

    public function test_position_target_and_the_parent_unit_result_shape_the_company_bonus(): void
    {
        $card = $this->approvedSpecCard();
        $this->mock(CompensationReadRepository::class, fn ($mock) => $mock->shouldReceive('baseAmountsFor')->andReturn(collect([$card->personnel->tabel_no => 1000.0])));
        $parent = Structure::query()->create(['name' => 'Departament', 'shortname' => 'DEP']);
        Structure::query()->whereKey($card->personnel->structure_id)->update(['parent_id' => $parent->id]);
        $bonus = app(BonusService::class);
        $cycle = $card->cycle;

        $bonus->saveRule($cycle, [
            ...$this->ruleData($bonus, $cycle),
            'position_targets' => [[$this->position->id, 30]],
            'unit_results' => [[$parent->id, 90]],
        ], $this->hr);
        $bonus->calculate($cycle);

        // 1000 × 3 months × 30% (position) × 50% payout × 0.8 (parent unit at 90%) = 360
        $line = PerformanceBonusCalculation::query()->firstOrFail();
        $this->assertSame(0.8, $line->unit_mult);
        $this->assertSame(360.0, $line->amount);

        $this->assertValidationKeys(['position_targets'], fn () => $bonus->saveRule($cycle, [...$this->ruleData($bonus, $cycle), 'position_targets' => [[$this->position->id, 30], [$this->position->id, 20]]], $this->hr));
    }

    public function test_bonus_export_and_award_orders_reach_payroll(): void
    {
        $card = $this->approvedSpecCard();
        $this->mock(CompensationReadRepository::class, fn ($mock) => $mock->shouldReceive('baseAmountsFor')->andReturn(collect([$card->personnel->tabel_no => 1000.0])));
        $bonus = app(BonusService::class);
        $bonus->calculate($card->cycle);
        $bonus->exportToPayroll($card->cycle);

        $this->assertDatabaseHas('payroll_one_off_earnings', ['tabel_no' => $card->personnel->tabel_no, 'code' => 'kpi_bonus', 'amount' => 225, 'pay_month' => now()->month]);

        Storage::fake('local');
        AwardType::query()->create(['id' => 20, 'name' => 'mükafatlar']);
        Award::query()->create(['id' => 2026, 'award_type_id' => 20, 'name' => 'Xidmətdə fərqləndiyinə görə']);
        $this->artisan('orders:seed-word-templates', ['--only' => 'pul_mukafati'])->assertSuccessful();
        $order = app(\App\Services\Orders\Document\OrderDraftService::class)->draft('pul_mukafati', $card->personnel, ['Məbləğ' => '300', 'Mükafatın səbəbi' => 'test'], 'M-1');

        app(OrderStatusTransitionService::class)->approve($order);
        $this->assertSame(300.0, PayrollOneOffEarning::query()->where('source_key', 'order_award:'.$order->id)->value('amount'));
        app(OrderStatusTransitionService::class)->cancel($order->refresh());
        $this->assertDatabaseMissing('payroll_one_off_earnings', ['source_key' => 'order_award:'.$order->id]);
    }

    public function test_rest_connector_fills_actuals_and_flags_a_failing_source_once(): void
    {
        Notification::fake();
        Sleep::fake();
        $card = $this->specExampleCard();
        app(ScorecardService::class)->transition($card, 'activate', $this->hr);
        $kpi = $card->items->first()->kpi;
        $kpi->update([
            'source_metric' => 'rest',
            'integration_config' => ['url' => 'https://erp.test/sales/{tabel_no}?from={from}', 'auth' => 'bearer', 'token' => 's3cret', 'value_path' => 'data.total'],
        ]);
        $this->assertStringNotContainsString('s3cret', (string) \Illuminate\Support\Facades\DB::table('performance_kpis')->where('id', $kpi->id)->value('integration_config'));

        Http::fake(['erp.test/*' => Http::sequence()->push(['data' => ['total' => 120000]])->whenEmpty(Http::response('down', 500))]);
        Carbon::setTestNow('2026-02-15');
        app(InternalKpiMetrics::class)->sync();
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer s3cret')
            && str_contains($request->url(), $card->personnel->tabel_no) && str_contains($request->url(), 'from=2026-01-01'));
        $this->assertSame('120000.0000', $card->items->first()->actuals()->where('source', 'integration')->value('value'));
        $this->assertNull($kpi->fresh()->integration_error);

        app(InternalKpiMetrics::class)->sync();
        app(InternalKpiMetrics::class)->sync();
        Carbon::setTestNow();

        $this->assertNotNull($kpi->fresh()->integration_error);
        $this->assertSame('120000.0000', $card->items->first()->actuals()->where('source', 'integration')->value('value'), 'The last value stays.');
        Notification::assertSentToTimes($this->hr, PlatformNotification::class, 1);
    }

    public function test_kpi_form_keeps_connector_secrets_on_the_server(): void
    {
        $this->hr->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        $this->person('Test');
        Http::fake(['erp.test/*' => Http::response(['total' => 42])]);

        $screen = Livewire::test(KpiLibraryWorkspace::class)
            ->call('openKpiForm')
            ->set('kpiForm.code', 'ERP_SALES')
            ->set('kpiForm.name', 'ERP satış')
            ->set('kpiForm.source_metric', 'rest')
            ->assertSee(__('performance_evaluation::kpi.connector.title'))
            ->set('connectorForm.url', 'https://erp.test/{tabel_no}')
            ->set('connectorForm.auth', 'bearer')
            ->set('connectorForm.secret', 'tok-1')
            ->set('connectorForm.value_path', 'total')
            ->call('testConnector')
            ->assertDispatched('notify', type: 'success')
            ->call('saveKpi')
            ->assertHasNoErrors();

        $kpi = PerformanceKpi::query()->where('code', 'ERP_SALES')->firstOrFail();
        $this->assertSame('tok-1', $kpi->integration_config['token']);
        $this->assertSame('integration', $kpi->data_source);

        $screen->call('openKpiForm', $kpi->id)
            ->assertSet('connectorForm.secret', '')
            ->assertDontSee('tok-1')
            ->set('connectorForm.value_path', 'data.total')
            ->call('saveKpi');
        $this->assertSame('tok-1', $kpi->fresh()->integration_config['token'], 'A blank secret keeps the stored one.');

        $screen->call('openKpiForm', $kpi->id)
            ->set('connectorForm.url', 'https://elsewhere.test/{tabel_no}')
            ->call('saveKpi');
        $this->assertSame('', $kpi->fresh()->integration_config['token'], 'The stored secret never follows the URL to a new host.');
    }

    public function test_stage_deadlines_skip_holidays_and_count_moved_working_days(): void
    {
        $card = $this->specExampleCard();
        Carbon::setTestNow('2026-03-16'); // Monday
        AttendanceCalendar::query()->create(['date' => '2026-03-17', 'day_type' => 'holiday', 'name' => 'Novruz', 'scope_type' => 'global']);
        AttendanceCalendar::query()->create(['date' => '2026-03-18', 'day_type' => 'holiday', 'name' => 'Novruz', 'scope_type' => 'global']);
        AttendanceCalendar::query()->create(['date' => '2026-03-21', 'day_type' => 'workday', 'name' => 'Köçürülmüş iş günü', 'scope_type' => 'global']);

        app(ScorecardService::class)->transition($card, 'send_for_agreement', $this->hr);
        Carbon::setTestNow();

        // 3 working days after Mon 16: Thu 19, Fri 20, Sat 21 (moved working day).
        $this->assertSame('2026-03-21', $card->fresh()->stage_due_at->toDateString());
    }

    public function test_long_leave_scales_additive_targets_and_prorata_then_restores_them(): void
    {
        $card = $this->specExampleCard();
        app(ScorecardService::class)->transition($card, 'activate', $this->hr);
        $sales = $card->items->first();
        $sales->kpiVersion->update(['snapshot' => [...$sales->kpiVersion->snapshot, 'aggregation' => 'sum']]);
        $leave = \App\Models\Leave::withoutEvents(fn () => \App\Models\Leave::query()->forceCreate([
            'tabel_no' => $card->personnel->tabel_no, 'leave_type_id' => 1, 'starts_at' => '2026-02-01', 'ends_at' => '2026-03-17',
            'total_days' => 45, 'status_id' => \App\Enums\OrderStatusEnum::APPROVED->value,
        ]));
        $lifecycle = app(ScorecardLifecycleService::class);

        $this->assertSame(1, $lifecycle->syncLongLeave());
        $this->assertSame(0, $lifecycle->syncLongLeave());
        $card->refresh();
        $this->assertSame(45, (int) $card->leave_days);
        $this->assertSame('0.5000', $card->prorata_factor); // 45 of 90 days worked
        $this->assertSame('50000.0000', $sales->fresh()->target);
        $this->assertSame('100000.0000', $sales->fresh()->original_target);
        $this->assertSame('20.0000', $card->items[1]->fresh()->target, 'Non-additive KPIs keep their target.');

        $leave->forceFill(['ends_at' => '2026-02-20', 'total_days' => 20])->save();
        $lifecycle->syncLongLeave();
        $this->assertSame('100000.0000', $sales->fresh()->target);
        $this->assertNull($sales->fresh()->original_target);
        $this->assertSame('1.0000', $card->fresh()->prorata_factor);
        $this->assertSame(2, $card->events()->where('action', 'leave_adjusted')->count());
    }

    public function test_internal_metric_refills_one_system_actual_per_item(): void
    {
        $card = $this->specExampleCard();
        app(ScorecardService::class)->transition($card, 'activate', $this->hr);
        $card->items->first()->kpi->update(['source_metric' => 'attendance_rate']);

        $this->app->instance(PayrollAttendanceReadRepository::class, new class implements PayrollAttendanceReadRepository
        {
            public function monthlyAbsence(string $tabelNo, int $year, int $month): ?array
            {
                return ['working_days' => 20, 'absence_days' => $month === 1 ? 2 : 0];
            }
        });

        Carbon::setTestNow('2026-02-15');
        $metrics = app(InternalKpiMetrics::class);
        $this->assertSame(1, $metrics->sync());
        $this->assertSame(1, $metrics->sync());
        Carbon::setTestNow();

        $actuals = $card->items->first()->actuals()->get();
        $this->assertCount(1, $actuals);
        $this->assertSame('hrm', $actuals->first()->source);
        $this->assertSame('95.0000', $actuals->first()->value);
    }

    public function test_company_bonus_pays_by_the_matrix_and_exports_once(): void
    {
        $card = $this->approvedSpecCard();
        $this->mock(CompensationReadRepository::class, fn ($mock) => $mock->shouldReceive('baseAmountsFor')->andReturn(collect([$card->personnel->tabel_no => 1000.0])));
        $bonus = app(BonusService::class);
        $cycle = $card->cycle;

        $this->assertSame('company', $bonus->mode());
        $preview = $bonus->preview($cycle, $bonus->rule($cycle));
        // 1000 × 3 months × 15% × 50% payout (score 81.5) = 225
        $this->assertSame(225.0, $preview['rows']->first()['amount']);
        $this->assertDatabaseCount('performance_bonus_calculations', 0);

        $bonus->saveRule($cycle, [...$this->ruleData($bonus, $cycle), 'fund' => 100, 'scale_to_fund' => true], $this->hr);
        $this->assertSame(1, $bonus->calculate($cycle));
        $this->assertFalse(is_numeric(\Illuminate\Support\Facades\DB::table('performance_bonus_calculations')->value('amount')), 'Stored encrypted.');
        $this->assertSame(100.0, PerformanceBonusCalculation::query()->first()?->amount);

        $this->assertCount(1, $bonus->exportToPayroll($cycle));
        $exportedAt = PerformanceBonusCalculation::query()->value('exported_at');
        $this->assertCount(1, $bonus->exportToPayroll($cycle));
        $this->assertEquals($exportedAt, PerformanceBonusCalculation::query()->value('exported_at'));

        $bonus->saveRule($cycle, [...$this->ruleData($bonus, $cycle), 'target_pct' => 50], $this->hr);
        $bonus->calculate($cycle);
        $this->assertSame(100.0, PerformanceBonusCalculation::query()->first()?->amount, 'An exported line never changes.');

        $this->assertValidationKeys(['bonus'], fn () => $bonus->issueOrders($cycle));
        $this->assertValidationKeys(['cap_pct'], fn () => $bonus->saveRule($cycle, [...$this->ruleData($bonus, $cycle), 'cap_pct' => 50], $this->hr));
    }

    public function test_military_regime_pays_the_bonus_as_an_award_order(): void
    {
        Storage::fake('local');
        $this->app->instance(ProfileState::class, new ProfileState([], 'military'));
        AwardType::query()->create(['id' => 20, 'name' => 'mükafatlar']);
        Award::query()->create(['id' => 2026, 'award_type_id' => 20, 'name' => 'Xidmətdə fərqləndiyinə görə']);
        $this->artisan('orders:seed-word-templates', ['--only' => 'pul_mukafati'])->assertSuccessful();

        $card = $this->approvedSpecCard();
        $this->mock(CompensationReadRepository::class, fn ($mock) => $mock->shouldReceive('baseAmountsFor')->andReturn(collect([$card->personnel->tabel_no => 1000.0])));
        $bonus = app(BonusService::class);
        $cycle = $card->cycle;

        $this->assertSame('order', $bonus->mode());
        $bonus->calculate($cycle);
        // 1000 × 1 salary × 50% payout = 500
        $this->assertSame(500.0, PerformanceBonusCalculation::query()->first()?->amount);
        $this->assertSame(1, $bonus->issueOrders($cycle));
        $this->assertSame(0, $bonus->issueOrders($cycle));

        $line = PerformanceBonusCalculation::query()->firstOrFail();
        $order = OrderLog::query()->findOrFail($line->order_log_id);
        $this->assertSame('ordered', $line->status);
        $this->assertSame(10, (int) $order->status_id);
        Storage::disk('local')->assertExists($order->template_snapshot['docx_path']);

        app(OrderStatusTransitionService::class)->approve($order);
        $this->assertDatabaseHas('personnel_awards', ['tabel_no' => $card->personnel->tabel_no, 'award_id' => 2026, 'amount' => 500, 'order_no' => $order->order_no]);

        app(OrderStatusTransitionService::class)->cancel($order->refresh());
        $this->assertDatabaseMissing('personnel_awards', ['order_no' => $order->order_no]);
    }

    public function test_bonus_screen_simulates_saves_and_calculates(): void
    {
        $card = $this->approvedSpecCard();
        $this->mock(CompensationReadRepository::class, fn ($mock) => $mock->shouldReceive('baseAmountsFor')->andReturn(collect([$card->personnel->tabel_no => 1000.0])));

        Livewire::test(BonusWorkspace::class)
            ->assertSee(__('performance_evaluation::kpi.bonus.modes.company.label'))
            ->assertSee('225.00')
            ->set('ruleForm.target_pct', 30)
            ->assertSee('450.00')
            ->assertSee(__('performance_evaluation::kpi.bonus.simulation_notice'))
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSee(__('performance_evaluation::kpi.bonus.statuses.calculated'));

        $this->assertSame(450.0, PerformanceBonusCalculation::query()->first()?->amount);
        $this->get(route('performance-evaluation', ['tab' => 'kpi_bonus']))->assertOk();
    }
}
