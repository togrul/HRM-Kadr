<?php

namespace Tests\Feature\EmployeeRelations;

use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseWorkflow;
use App\Modules\EmployeeRelations\Application\Services\DecisionService;
use App\Modules\EmployeeRelations\Application\Services\DisciplinaryDeadline;
use App\Modules\EmployeeRelations\Application\Services\EvidenceService;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvidence;
use App\Modules\EmployeeRelations\Livewire\CaseDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\EmployeeRelations\Concerns\BuildsEmployeeRelationCases;
use Tests\TestCase;

/**
 * Qərar → intizam əmri qaralaması (OrderDrafter kontraktı) və ƏM Md.187–189 müddət qaydaları.
 */
class DecisionAndDeadlineTest extends TestCase
{
    use BuildsEmployeeRelationCases;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 10:00:00');
        $this->erFakeDrafter();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: RelationCase, 1: \App\Models\User, 2: \App\Models\Personnel}
     */
    private function investigatingCase(array $overrides = []): array
    {
        $structure = $this->erStructure('İstehsalat');
        $subject = $this->erPersonnel('Cəfərov', $structure->id);
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        $case = $this->erOpenCase($hr, $subject, $overrides);
        app(CaseWorkflow::class)->transition($case, CaseStatus::Investigating, $hr);

        return [$case->fresh(), $hr, $subject];
    }

    public function test_reprimand_drafts_a_disciplinary_order_and_links_it_to_the_case(): void
    {
        [$case, $hr, $subject] = $this->investigatingCase([
            'occurred_at' => '2026-06-01',
            'discovered_at' => '2026-06-05',
        ]);
        $subjectUser = $this->erUser([]);
        $this->erLink($subjectUser, $subject);

        app(EvidenceService::class)->add($case, $hr, RelationCaseEvidence::KIND_EXPLANATION, 'İzahat', 'Nəqliyyat problemi oldu.', null, $subject->id);

        app(DecisionService::class)->decide($case, $hr, DecisionOutcome::Reprimand, 'Təkrar gecikmələr təsdiqləndi.');

        $case->refresh();
        $this->assertSame(CaseStatus::Decided, $case->status);
        $this->assertSame(DecisionOutcome::Reprimand, $case->decision_outcome);
        $this->assertSame(9001, $case->order_log_id);
        $this->assertNotNull($case->decision_notified_at);

        $this->assertCount(1, $this->draftedOrders);
        $this->assertSame('intizam_tenbehi', $this->draftedOrders[0]['template']);
        $this->assertSame($subject->id, $this->draftedOrders[0]['personnel_id']);
        $this->assertSame($case->case_number, $this->draftedOrders[0]['number']);
        $this->assertSame([
            DecisionService::FIELD_VIOLATION => 'İşə gecikmə',
            DecisionService::FIELD_SANCTION => 'töhmət',
            DecisionService::FIELD_BASIS => $case->case_number.' saylı xidməti araşdırmanın materialları və işçinin izahatı',
        ], $this->draftedOrders[0]['fields']);

        $this->assertDatabaseHas('employee_relation_case_events', ['case_id' => $case->id, 'action' => 'order_drafted']);
        $this->assertSame('decision', $subjectUser->notifications()->first()?->data['kind']);
    }

    public function test_severe_reprimand_uses_the_final_warning_wording(): void
    {
        [$case, $hr] = $this->investigatingCase(['occurred_at' => '2026-06-01', 'discovered_at' => '2026-06-10']);

        app(DecisionService::class)->decide($case, $hr, DecisionOutcome::SevereReprimand, 'Ağır pozuntu.');

        $this->assertSame('sonuncu xəbərdarlıqla şiddətli töhmət', $this->draftedOrders[0]['fields'][DecisionService::FIELD_SANCTION]);
    }

    public function test_sanction_is_blocked_one_month_after_discovery_but_a_warning_is_still_allowed(): void
    {
        [$case, $hr] = $this->investigatingCase(['occurred_at' => '2026-05-01', 'discovered_at' => '2026-05-10']);

        $evaluation = app(DisciplinaryDeadline::class)->evaluate($case);
        $this->assertTrue($evaluation['blocked']);
        $this->assertSame('2026-06-10', $evaluation['deadline']);
        $this->assertContains('deadline_passed', array_column($evaluation['messages'], 'code'));

        try {
            app(DecisionService::class)->decide($case, $hr, DecisionOutcome::Reprimand, 'Gecikmiş tənbeh');
            $this->fail('sanction after the Art. 187.2 limit');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('decision_outcome', $exception->errors());
            $this->assertSame(CaseStatus::Investigating, $case->fresh()->status);
            $this->assertSame([], $this->draftedOrders);
        }

        app(DecisionService::class)->decide($case->fresh(), $hr, DecisionOutcome::WrittenWarning, 'Yazılı xəbərdarlıq.');
        $this->assertSame(DecisionOutcome::WrittenWarning, $case->fresh()->decision_outcome);
        $this->assertNull($case->fresh()->order_log_id);
    }

    public function test_six_months_from_the_misconduct_caps_the_limit_unless_found_by_audit(): void
    {
        [$case] = $this->investigatingCase(['occurred_at' => '2025-12-01', 'discovered_at' => '2026-06-01']);

        $evaluation = app(DisciplinaryDeadline::class)->evaluate($case);
        $this->assertTrue($evaluation['blocked']);
        $this->assertSame('2026-06-01', $evaluation['commission_deadline']);

        $case->forceFill(['discovered_by_audit' => true])->save();
        $audit = app(DisciplinaryDeadline::class)->evaluate($case->fresh());
        $this->assertFalse($audit['blocked']);
        $this->assertSame('2027-12-01', $audit['commission_deadline']);
        $this->assertSame('2026-07-01', $audit['deadline']);
    }

    public function test_vacation_days_extend_the_discovery_limit(): void
    {
        [$case, , $subject] = $this->investigatingCase(['occurred_at' => '2026-05-01', 'discovered_at' => '2026-05-10']);
        $this->erFakeVacation((string) $subject->tabel_no, '2026-05-20', '2026-06-08');

        $evaluation = app(DisciplinaryDeadline::class)->evaluate($case);

        $this->assertSame(20, $evaluation['excluded_days']);
        $this->assertSame('2026-06-30', $evaluation['deadline']);
        $this->assertFalse($evaluation['blocked']);
        $this->assertContains('excluded_days', array_column($evaluation['messages'], 'code'));
        $this->assertContains('explanation_missing', array_column($evaluation['messages'], 'code'));
    }

    public function test_missing_dates_subject_template_or_an_existing_order_block_the_sanction(): void
    {
        [$case, $hr] = $this->investigatingCase(['occurred_at' => null]);
        $this->assertContains('dates_required', array_column(app(DisciplinaryDeadline::class)->evaluate($case)['messages'], 'code'));

        $case->forceFill(['occurred_at' => '2026-06-10', 'order_log_id' => 55])->save();
        $this->assertContains('already_sanctioned', array_column(app(DisciplinaryDeadline::class)->evaluate($case->fresh())['messages'], 'code'));

        $case->forceFill(['order_log_id' => null, 'subject_personnel_id' => null])->save();
        $this->assertContains('subject_required', array_column(app(DisciplinaryDeadline::class)->evaluate($case->fresh())['messages'], 'code'));

        [$withSubject, $hr2] = $this->investigatingCase(['occurred_at' => '2026-06-10', 'discovered_at' => '2026-06-12']);
        $this->fakeTemplates = [];

        try {
            app(DecisionService::class)->decide($withSubject, $hr2, DecisionOutcome::Reprimand, 'Şablonsuz');
            $this->fail('no disciplinary template');
        } catch (ValidationException $exception) {
            $this->assertSame(__('employee_relations::cases.messages.template_missing'), $exception->errors()['decision_outcome'][0]);
        }
    }

    public function test_decision_screen_shows_the_legal_warning_and_disables_sanctions_after_the_limit(): void
    {
        [$case, $hr] = $this->investigatingCase(['occurred_at' => '2026-05-01', 'discovered_at' => '2026-05-10']);

        $component = Livewire::actingAs($hr)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->assertSee(__('employee_relations::cases.deadline.deadline_passed', [
                'date' => '10.06.2026',
                'rule' => __('employee_relations::cases.deadline.rules.discovery'),
            ]));

        $options = $component->instance()->decisionOptions();
        $this->assertTrue($options['reprimand']['disabled']);
        $this->assertTrue($options['severe_reprimand']['disabled']);
        $this->assertFalse($options['verbal_warning']['disabled']);

        $component->set('decisionOutcome', 'reprimand')
            ->set('decisionSummary', 'Cəhd')
            ->call('decide')
            ->assertHasErrors('decision_outcome');

        $component->set('decisionOutcome', 'verbal_warning')
            ->set('decisionSummary', 'Şifahi xəbərdarlıq edildi.')
            ->call('decide')
            ->assertHasNoErrors();

        $this->assertSame(CaseStatus::Decided, $case->fresh()->status);
    }

    public function test_only_case_managers_can_decide(): void
    {
        [$case, $hr] = $this->investigatingCase(['occurred_at' => '2026-06-10', 'discovered_at' => '2026-06-12']);
        $investigator = $this->erUser([CaseAccess::SHOW], [(int) $case->structure_id]);
        app(\App\Modules\EmployeeRelations\Application\Services\CaseService::class)->assignInvestigator($case, $hr, $investigator->id);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(DecisionService::class)->decide($case, $investigator, DecisionOutcome::Reprimand, 'Araşdırıcı qərar verə bilməz');
    }
}
