<?php

namespace Tests\Feature\EmployeeRelations;

use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseService;
use App\Modules\EmployeeRelations\Application\Services\CaseWorkflow;
use App\Modules\EmployeeRelations\Application\Services\DecisionService;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use App\Modules\EmployeeRelations\Livewire\CaseDetail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\EmployeeRelations\Concerns\BuildsEmployeeRelationCases;
use Tests\TestCase;

/**
 * Status keçidləri (yalnız icazəli qraf), tarixçə və SLA (son tarix, gecikmə, xatırlatma).
 */
class CaseWorkflowTest extends TestCase
{
    use BuildsEmployeeRelationCases;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_only_allowed_transitions_are_accepted_and_each_is_recorded(): void
    {
        $structure = $this->erStructure('Anbar');
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        $investigator = $this->erUser([CaseAccess::SHOW], [$structure->id], name: 'Araşdırıcı Ağayev');
        $case = $this->erOpenCase($hr, $this->erPersonnel('Həsənov', $structure->id));
        app(CaseService::class)->assignInvestigator($case, $hr, $investigator->id);

        $workflow = app(CaseWorkflow::class);

        try {
            $workflow->transition($case, CaseStatus::Decided, $hr, 'qərarsız keçid');
            $this->fail('open → decided must go through the decision');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('transition', $exception->errors());
        }

        try {
            $workflow->transition($case, CaseStatus::Closed, $investigator, 'araşdırıcı bağlaya bilməz');
            $this->fail('an investigator must not close a case');
        } catch (AuthorizationException) {
            $this->assertSame(CaseStatus::Open, $case->fresh()->status);
        }

        $workflow->transition($case, CaseStatus::Investigating, $investigator, 'Araşdırmaya başladım');

        $case->refresh();
        $this->assertSame(CaseStatus::Investigating, $case->status);
        $this->assertSame(now()->addDays(20)->toDateString(), $case->stage_due_at?->toDateString());
        $this->assertDatabaseHas('employee_relation_case_events', [
            'case_id' => $case->id,
            'action' => 'transition',
            'from_status' => 'open',
            'to_status' => 'investigating',
            'comment' => 'Araşdırmaya başladım',
            'actor_user_id' => $investigator->id,
        ]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'employee_relations', 'event' => 'status_changed', 'subject_id' => $case->id, 'causer_id' => $investigator->id]);

        Livewire::actingAs($hr)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->assertSee('Araşdırmaya başladım')
            ->assertSee('Araşdırıcı Ağayev');
    }

    public function test_closing_requires_a_comment_and_waits_for_the_appeal_window(): void
    {
        Carbon::setTestNow('2026-03-02 10:00:00');
        $this->erFakeDrafter();

        $structure = $this->erStructure('Anbar');
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        $open = $this->erOpenCase($hr, $this->erPersonnel('Həsənov', $structure->id));

        try {
            app(CaseWorkflow::class)->transition($open, CaseStatus::Closed, $hr, '  ');
            $this->fail('closing without a comment');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('comment', $exception->errors());
        }

        app(CaseWorkflow::class)->transition($open, CaseStatus::Investigating, $hr);
        app(DecisionService::class)->decide($open->fresh(), $hr, DecisionOutcome::VerbalWarning, 'Şifahi xəbərdarlıq edildi.');

        $decided = $open->fresh();
        $this->assertSame('2026-03-12', $decided->appeal_deadline?->toDateString());

        try {
            app(CaseWorkflow::class)->transition($decided, CaseStatus::Closed, $hr, 'Bağlanır');
            $this->fail('closing inside the appeal window');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('transition', $exception->errors());
        }

        Carbon::setTestNow('2026-03-13 09:00:00');
        app(CaseWorkflow::class)->transition($decided->fresh(), CaseStatus::Closed, $hr, 'Apellyasiya olmadı, bağlanır');

        $closed = $decided->fresh();
        $this->assertSame(CaseStatus::Closed, $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertNull($closed->stage_due_at);
    }

    public function test_late_stage_exit_counts_an_sla_breach_and_the_type_overrides_investigation_days(): void
    {
        Carbon::setTestNow('2026-04-01 09:00:00');
        RelationCaseType::query()->where('code', 'disciplinary')->update(['investigation_sla_days' => 7]);

        $structure = $this->erStructure('Anbar');
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        $case = $this->erOpenCase($hr, $this->erPersonnel('Həsənov', $structure->id));

        Carbon::setTestNow('2026-04-06 09:00:00');
        $this->assertTrue($case->fresh()->isOverdue());

        app(CaseWorkflow::class)->transition($case->fresh(), CaseStatus::Investigating, $hr);

        $case->refresh();
        $this->assertSame(1, $case->sla_breach_count);
        $this->assertSame('2026-04-13', $case->stage_due_at?->toDateString());
        $this->assertFalse($case->isOverdue());
    }

    public function test_sla_reminder_command_notifies_investigators_once_per_cooldown(): void
    {
        Carbon::setTestNow('2026-05-01 09:00:00');

        $structure = $this->erStructure('Anbar');
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        $investigator = $this->erUser([CaseAccess::SHOW], [$structure->id]);
        $overdue = $this->erOpenCase($hr, $this->erPersonnel('Həsənov', $structure->id));
        $ownerOnly = $this->erOpenCase($hr, $this->erPersonnel('Məmmədov', $structure->id));
        app(CaseService::class)->assignInvestigator($overdue, $hr, $investigator->id);

        Carbon::setTestNow('2026-05-02 09:00:00');
        $fresh = $this->erOpenCase($hr, $this->erPersonnel('Yeniyev', $structure->id));

        Carbon::setTestNow('2026-05-05 09:00:00');

        $this->assertSame(0, Artisan::call('employee-relations:sla-reminders', ['--json' => true]));
        $result = json_decode(Artisan::output(), true);

        $this->assertSame(2, $result['overdue']);
        $this->assertSame(2, $result['reminded_cases']);
        $this->assertSame(1, $investigator->notifications()->count());
        $this->assertSame('employeeRelationsCase', $investigator->notifications()->first()->data['action']);
        $this->assertSame('sla_overdue', $investigator->notifications()->first()->data['kind']);
        // Araşdırıcı təyin olunmayan işdə xatırlatma işi açana gedir.
        $this->assertSame(1, $hr->notifications()->count());
        $this->assertSame(1, $overdue->fresh()->reminder_count);
        $this->assertSame(0, $fresh->fresh()->reminder_count);
        $this->assertNotNull($ownerOnly->fresh()->last_reminder_at);

        Artisan::call('employee-relations:sla-reminders', ['--json' => true]);
        $this->assertSame(0, json_decode(Artisan::output(), true)['reminded_cases']);
        $this->assertSame(1, $investigator->notifications()->count());

        Carbon::setTestNow('2026-05-06 10:00:00');
        Artisan::call('employee-relations:sla-reminders', ['--json' => true]);
        $this->assertSame(2, $investigator->fresh()->notifications()->count());
    }

    public function test_overdue_cases_are_flagged_in_the_list(): void
    {
        Carbon::setTestNow('2026-05-01 09:00:00');
        $structure = $this->erStructure('Anbar');
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        $this->erOpenCase($hr, $this->erPersonnel('Həsənov', $structure->id), ['title' => 'Gecikən iş']);

        Carbon::setTestNow('2026-05-10 09:00:00');
        $this->erOpenCase($hr, $this->erPersonnel('Məmmədov', $structure->id), ['title' => 'Vaxtında iş']);

        Livewire::actingAs($hr)
            ->test(\App\Modules\EmployeeRelations\Livewire\Dashboard::class)
            ->set('overdueOnly', true)
            ->assertSee('Gecikən iş')
            ->assertDontSee('Vaxtında iş')
            ->assertSee(__('employee_relations::cases.labels.overdue_since', ['date' => '04.05.2026']));

        $this->assertSame(1, RelationCase::query()->get()->filter->isOverdue()->count());
    }
}
