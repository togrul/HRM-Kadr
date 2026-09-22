<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceBonusCalculation;
use App\Models\PerformanceKpi;
use App\Models\PerformanceNotificationSetting;
use App\Models\PerformanceNotificationTemplate;
use App\Models\PerformanceScorecard;
use App\Models\Position;
use App\Models\User;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\BonusService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiLibraryService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiTemplateService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardLifecycleService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\TargetChangeService;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\AnalyticsWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\KpiLibraryWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\ScorecardsWorkspace;
use App\Modules\PerformanceEvaluation\Support\KpiMail;
use App\Modules\Personnel\Contracts\ApprovalRouteResolver;
use App\Notifications\PlatformNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\PerformanceEvaluation\Concerns\BuildsKpiCards;
use Tests\TestCase;

class KpiPhaseFourTest extends TestCase
{
    use BuildsKpiCards;
    use RefreshDatabase;

    private Position $position;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->position = Position::query()->create(['name' => 'Satış meneceri']);
        $this->hr = User::factory()->create(['email' => 'hr@example.test']);
        $this->hr->givePermissionTo(Permission::findOrCreate('manage-performance-evaluation', 'web'));
        $this->hr->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        $this->actingAs($this->hr);
    }

    public function test_active_targets_change_only_through_an_approved_request(): void
    {
        Notification::fake();
        $card = $this->specExampleCard();
        [$employee, $manager] = $this->employeeAndManager($card);
        app(ScorecardService::class)->transition($card, 'activate', $this->hr);
        $sales = $card->items->first();
        $changes = app(TargetChangeService::class);

        $this->assertValidationKeys(['target'], fn () => app(ScorecardService::class)->updateTarget($sales, 1, $this->hr));
        $this->assertValidationKeys(['reason'], fn () => $changes->request($sales, 90000, ' ', $manager));

        $request = $changes->request($sales, 90000, 'Bazar daraldı', $manager);
        $this->assertValidationKeys(['change'], fn () => $changes->request($sales, 80000, 'İkinci', $employee));
        Notification::assertSentTo($this->hr, PlatformNotification::class);

        $this->expectExceptionObject(new AuthorizationException);
        try {
            $changes->decide($request, true, null, $manager);
        } finally {
            $this->assertValidationKeys(['reason'], fn () => $changes->decide($request->fresh(), false, '', $this->hr));
            $changes->decide($request->fresh(), true, null, $this->hr);

            $this->assertSame('90000.0000', $sales->fresh()->target);
            $this->assertSame('approved', $request->fresh()->status);
            $this->assertSame(1, $card->events()->where('action', 'target_change_approved')->count());
            Notification::assertSentTo($manager, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'change_approved');
        }
    }

    public function test_a_new_manager_takes_the_card_over(): void
    {
        Notification::fake();
        $card = $this->specExampleCard();
        [, $oldManager] = $this->employeeAndManager($card);
        $newManagerPerson = $this->person('Yeni');
        $newManager = $this->userFor($newManagerPerson);
        $this->mock(ApprovalRouteResolver::class, fn ($mock) => $mock->shouldReceive('manager')->andReturn(['id' => $newManagerPerson->id]));

        $result = app(ScorecardLifecycleService::class)->syncPersonnel();

        $this->assertSame(1, $result['manager_changed']);
        $card->refresh();
        $this->assertSame($newManagerPerson->id, $card->manager_personnel_id);
        $this->assertSame('manager', app(ScorecardService::class)->roleFor($newManager, $card));
        $this->assertNull(app(ScorecardService::class)->roleFor($oldManager, $card));
        Notification::assertSentTo($newManager, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'manager_assigned');
        Notification::assertSentTo($oldManager, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'manager_released');
    }

    public function test_probation_blocks_the_bonus_unless_the_rule_allows_it(): void
    {
        $card = $this->approvedSpecCard();
        $card->personnel->forceFill(['join_work_date' => '2026-01-15', 'probation_unit' => 'month', 'probation_amount' => 6])->saveQuietly();
        $this->mock(CompensationReadRepository::class, fn ($mock) => $mock->shouldReceive('baseAmountsFor')->andReturn(collect([$card->personnel->tabel_no => 1000.0])));
        $bonus = app(BonusService::class);

        $row = $bonus->preview($card->cycle, $bonus->rule($card->cycle))['rows']->first();
        $this->assertTrue($row['probation']);
        $this->assertSame(0.0, $row['amount']);

        $rule = $bonus->rule($card->cycle)->fill(['pay_in_probation' => true]);
        $this->assertGreaterThan(0, $bonus->preview($card->cycle, $rule)['rows']->first()['amount']);
    }

    public function test_a_formula_kpi_is_calculated_from_the_other_kpis_on_the_card(): void
    {
        $plan = $this->kpi('PLAN');
        $fact = $this->kpi('FACT');
        $rate = app(KpiLibraryService::class)->save([
            'code' => 'RATE', 'name' => 'İcra', 'type' => 'quantitative', 'direction' => 'higher_better', 'unit' => 'percent',
            'frequency' => 'quarterly', 'aggregation' => 'last', 'perspective' => 'financial', 'status' => 'active',
            'data_source' => 'calculated', 'formula' => 'ROUND({FACT} / {PLAN} * 100, 1)',
        ]);
        app(KpiTemplateService::class)->save($this->templateData(), [
            $this->item($plan, 10, target: 100), $this->item($fact, 10, target: 100), $this->item($rate, 80, target: 100),
        ], [$this->position->id]);
        $person = $this->person('Formula');
        $cycle = \App\Models\PerformanceCycle::query()->create(['name' => 'Q', 'cycle_type' => 'quarterly', 'period_start' => '2026-01-01', 'period_end' => '2026-03-31', 'status' => 'active']);
        app(ScorecardService::class)->generateForCycle($cycle);
        $card = PerformanceScorecard::query()->where('personnel_id', $person->id)->with('items.kpi')->firstOrFail();
        $service = app(ScorecardService::class);
        $service->transition($card, 'activate', $this->hr);

        $this->assertValidationKeys(['actual'], fn () => $service->recordActual($card->items[2], 5, $this->hr));
        $service->recordActual($card->items[0], 200, $this->hr);
        $service->recordActual($card->items[1], 190, $this->hr);

        $this->assertSame('95.0000', $card->items[2]->fresh()->actual);
    }

    public function test_formula_check_rejects_unknown_codes_and_cycles(): void
    {
        $this->kpi('A_KPI');
        app(KpiLibraryService::class)->save([
            'code' => 'B_KPI', 'name' => 'B', 'type' => 'quantitative', 'direction' => 'higher_better', 'unit' => 'count',
            'frequency' => 'quarterly', 'aggregation' => 'last', 'perspective' => 'financial', 'status' => 'active',
            'data_source' => 'calculated', 'formula' => '{C_KPI} + 1',
        ]);

        $form = fn (string $formula) => Livewire::test(KpiLibraryWorkspace::class)
            ->call('openKpiForm')
            ->set('kpiForm.code', 'C_KPI')
            ->set('kpiForm.name', 'C')
            ->set('kpiForm.source_metric', 'formula')
            ->set('kpiForm.formula', $formula)
            ->call('saveKpi');

        $form('{NOPE} * 2')->assertHasErrors('kpiForm.formula');
        $form('{B_KPI} * 2')->assertHasErrors('kpiForm.formula');
        $form('{A_KPI} * 2')->assertHasNoErrors();
        $this->assertSame('calculated', PerformanceKpi::query()->where('code', 'C_KPI')->value('data_source'));
    }

    public function test_hr_opens_a_half_time_card_for_a_second_post(): void
    {
        $card = $this->specExampleCard();
        $second = Position::query()->create(['name' => 'Təlimçi']);
        app(KpiTemplateService::class)->save([...$this->templateData(), 'name' => 'Təlim'], [$this->item($this->kpi('TRAINING'), 100)], [$second->id]);

        Livewire::test(ScorecardsWorkspace::class)
            ->set('cycleId', $card->performance_cycle_id)
            ->call('openExtra')
            ->assertSee(__('performance_evaluation::kpi.extra.fte_hint'))
            ->set('extraPersonnelId', $card->personnel_id)
            ->set('extraPositionId', $second->id)
            ->set('extraFte', 0.5)
            ->assertSee('Təlimçi')
            ->call('openExtraCard')
            ->assertHasNoErrors();

        $extra = PerformanceScorecard::query()->where('position_id', $second->id)->firstOrFail();
        $this->assertTrue($extra->is_additional);
        $this->assertSame('0.50', $extra->fte);

        $result = app(ScorecardLifecycleService::class)->syncPersonnel();
        $this->assertSame(0, $result['position_changed'], 'The second post is not a position change.');
    }

    public function test_forecast_extrapolates_additive_kpis_and_warns_once_in_the_red(): void
    {
        Notification::fake();
        $card = $this->specExampleCard();
        [$employee] = $this->employeeAndManager($card);
        $service = app(ScorecardService::class);
        $service->transition($card, 'activate', $this->hr);
        $sales = $card->items->first();
        $sales->kpiVersion->update(['snapshot' => [...$sales->kpiVersion->snapshot, 'aggregation' => 'sum']]);

        Carbon::setTestNow('2026-02-14'); // day 45 of 90
        $service->recordActual($sales, 30000, $this->hr);
        $this->assertSame('60000.0000', $sales->fresh()->forecast);
        $this->assertSame('60.0000', $sales->fresh()->forecast_achievement);

        $lifecycle = app(ScorecardLifecycleService::class);
        $this->assertSame(1, $lifecycle->notifyRedZone());
        $this->assertSame(0, $lifecycle->notifyRedZone());
        Carbon::setTestNow();

        Notification::assertSentTo($employee, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'red_zone');
    }

    public function test_notifications_follow_channel_settings_digest_and_templates(): void
    {
        Notification::fake();
        $card = $this->specExampleCard();
        [$employee, $manager] = $this->employeeAndManager($card);
        $employee->forceFill(['email' => 'emp@example.test'])->save();
        $manager->forceFill(['email' => 'man@example.test'])->save();
        PerformanceNotificationSetting::query()->create(['user_id' => $employee->id, 'email' => false]);
        PerformanceNotificationSetting::query()->create(['user_id' => $manager->id, 'email' => true, 'digest' => true]);
        PerformanceNotificationTemplate::query()->create(['key' => 'agreement_requested', 'locale' => app()->getLocale(), 'subject' => 'Salam {employee_name}', 'body' => 'Son tarix {deadline}. {link}']);

        $service = app(ScorecardService::class);
        $service->transition($card, 'send_for_agreement', $this->hr);

        // Mandatory: e-mailed even with e-mail off, in HR's wording.
        Notification::assertSentTo($employee, KpiMail::class, fn (KpiMail $mail) => str_starts_with($mail->subject, 'Salam ') && str_contains($mail->lines[0], 'Son tarix '));

        $service->transition($card->refresh(), 'reject', $employee, 'Hədəf yüksəkdir');
        Notification::assertSentTo($manager, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'rejected');
        Notification::assertNotSentTo($manager, KpiMail::class);
    }

    public function test_the_daily_run_opens_chases_and_warns(): void
    {
        Notification::fake();
        $card = $this->specExampleCard();
        [$employee, $manager] = $this->employeeAndManager($card);
        app(ScorecardService::class)->transition($card, 'activate', $this->hr);
        $lifecycle = app(ScorecardLifecycleService::class);

        Carbon::setTestNow('2026-02-10');
        $this->assertSame(['actuals_missing' => 0, 'checkin_due' => 1], $lifecycle->remindActualsAndCheckins());
        $this->assertSame(['actuals_missing' => 0, 'checkin_due' => 0], $lifecycle->remindActualsAndCheckins());

        Carbon::setTestNow('2026-03-29');
        $this->assertSame(1, $lifecycle->remindActualsAndCheckins()['actuals_missing']);
        Carbon::setTestNow();

        Notification::assertSentTo($employee, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'actuals_missing');
        Notification::assertSentTo($manager, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'checkin_due');
    }

    public function test_managers_hear_when_the_cycle_opens_and_digest_users_get_one_mail(): void
    {
        Notification::fake();
        $boss = $this->person('Rəis');
        $boss->forceFill(['position_id' => Position::query()->create(['name' => 'Rəis'])->id])->saveQuietly();
        $bossUser = $this->userFor($boss);
        $bossUser->forceFill(['email' => 'boss@example.test'])->save();
        PerformanceNotificationSetting::query()->create(['user_id' => $bossUser->id, 'email' => true, 'digest' => true]);
        $this->mock(ApprovalRouteResolver::class, fn ($mock) => $mock->shouldReceive('manager')->andReturn(['id' => $boss->id]));

        $card = $this->specExampleCard();

        $this->assertSame($boss->id, $card->manager_personnel_id);
        Notification::assertSentTo($bossUser, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'cycle_opened');
        Notification::assertNotSentTo($bossUser, KpiMail::class);
    }

    public function test_the_daily_digest_collects_the_optional_kpi_events(): void
    {
        $user = User::factory()->create(['email' => 'digest@example.test']);
        PerformanceNotificationSetting::query()->create(['user_id' => $user->id, 'email' => true, 'digest' => true]);
        app(\App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiNotificationDelivery::class)->deliver([$user->id], 'checkin_due', ['employee' => 'Əliyev', 'cycle' => 'Q1']);

        Notification::fake();
        $this->assertSame(1, app(\App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiNotificationDelivery::class)->sendDigests());
        Notification::assertSentTo($user, KpiMail::class, fn (KpiMail $mail) => count($mail->lines) === 1 && str_contains($mail->lines[0], 'Əliyev'));
    }

    public function test_bonus_over_the_fund_warns_hr(): void
    {
        Notification::fake();
        $card = $this->approvedSpecCard();
        $this->mock(CompensationReadRepository::class, fn ($mock) => $mock->shouldReceive('baseAmountsFor')->andReturn(collect([$card->personnel->tabel_no => 1000.0])));
        $bonus = app(BonusService::class);
        $bonus->saveRule($card->cycle, [...$this->ruleData($bonus, $card->cycle), 'fund' => 100], $this->hr);

        $bonus->calculate($card->cycle);

        Notification::assertSentTo($this->hr, PlatformNotification::class, fn ($notification) => $notification->payload['event'] === 'fund_exceeded');
        $this->assertSame(225.0, PerformanceBonusCalculation::query()->first()->amount);
    }

    public function test_analytics_shows_each_role_its_own_section_and_exports(): void
    {
        $card = $this->approvedSpecCard();
        [$employee, $manager] = $this->employeeAndManager($card);
        foreach ([$employee, $manager] as $user) {
            $user->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        }

        Livewire::test(AnalyticsWorkspace::class)
            ->assertSee(__('performance_evaluation::kpi.analytics.progress.title'))
            ->assertSee(__('performance_evaluation::kpi.analytics.strictness.title'))
            ->call('export', 'strictness')
            ->assertFileDownloaded('kpi-strictness-'.$card->performance_cycle_id.'.xlsx');

        $this->actingAs($employee);
        Livewire::test(AnalyticsWorkspace::class)
            ->assertSee(__('performance_evaluation::kpi.analytics.mine.title'))
            ->assertDontSee(__('performance_evaluation::kpi.analytics.strictness.title'));

        $this->actingAs($manager);
        Livewire::test(AnalyticsWorkspace::class)
            ->assertSee(__('performance_evaluation::kpi.analytics.team.title'))
            ->assertSee('SALES');
    }

    public function test_the_card_prints_with_signature_fields_for_those_who_may_see_it(): void
    {
        $card = $this->approvedSpecCard();

        $this->get(route('performance-evaluation.scorecard-print', $card->id))
            ->assertOk()
            ->assertSee($card->personnel->fullname)
            ->assertSee(__('performance_evaluation::kpi.print.signatures.hr'))
            ->assertSee('81.5%');

        $stranger = User::factory()->create();
        $stranger->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        $this->actingAs($stranger)->get(route('performance-evaluation.scorecard-print', $card->id))->assertNotFound();
    }
}
