<?php

namespace Tests\Feature\EmployeeRelations;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\EmployeeRelations\Application\Services\AppealService;
use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseWorkflow;
use App\Modules\EmployeeRelations\Application\Services\DecisionService;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseAppeal;
use App\Modules\EmployeeRelations\Livewire\CaseDetail;
use App\Modules\EmployeeRelations\Livewire\MyCases;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\EmployeeRelations\Concerns\BuildsEmployeeRelationCases;
use Tests\TestCase;

/**
 * Apellyasiya: işçi Şəxsi kabinetdən müddət ərzində etiraz edir, HR ayrıca yenidən
 * baxış aparır və yekun qərar verir.
 */
class AppealTest extends TestCase
{
    use BuildsEmployeeRelationCases;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-01 10:00:00');
        $this->erFakeDrafter();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{0: RelationCase, 1: User, 2: User, 3: Personnel}
     */
    private function decidedCase(DecisionOutcome $outcome = DecisionOutcome::WrittenWarning): array
    {
        $structure = $this->erStructure('Logistika');
        $subject = $this->erPersonnel('Etirazçıyev', $structure->id);
        $subjectUser = $this->erUser([]);
        $this->erLink($subjectUser, $subject);
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);

        $case = $this->erOpenCase($hr, $subject, ['occurred_at' => '2026-06-25', 'discovered_at' => '2026-06-28']);
        app(CaseWorkflow::class)->transition($case, CaseStatus::Investigating, $hr);
        app(DecisionService::class)->decide($case->fresh(), $hr, $outcome, 'Qərarın əsaslandırılması.');

        return [$case->fresh(), $hr, $subjectUser, $subject];
    }

    public function test_subject_appeals_from_my_hr_within_the_window_and_hr_reviews_it(): void
    {
        [$case, $hr, $subjectUser, $subject] = $this->decidedCase();

        Livewire::actingAs($subjectUser)
            ->test(MyCases::class)
            ->call('startAppeal', $case->id)
            ->set('appealReason', 'qısa')
            ->call('submitAppeal')
            ->assertHasErrors('appealReason')
            ->set('appealReason', 'Gecikmə nəqliyyat qəzası səbəbindən olub, sənəd təqdim edirəm.')
            ->call('submitAppeal')
            ->assertHasNoErrors()
            ->assertSee(__('employee_relations::cases.appeal_statuses.pending'));

        $case->refresh();
        $this->assertSame(CaseStatus::Appealed, $case->status);
        $this->assertDatabaseHas('employee_relation_case_appeals', [
            'case_id' => $case->id,
            'appellant_personnel_id' => $subject->id,
            'appellant_role' => 'subject',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('employee_relation_case_events', ['case_id' => $case->id, 'action' => 'appeal_submitted', 'actor_user_id' => $subjectUser->id]);

        // İkinci apellyasiya verilə bilməz.
        $this->assertFalse(app(AppealService::class)->canAppeal($case, $subjectUser, $subject->id));

        Livewire::actingAs($hr)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->assertSee('Gecikmə nəqliyyat qəzası səbəbindən olub')
            ->call('startAppealReview')
            ->set('appealOutcome', 'annulled')
            ->set('appealComment', 'Üzrlü səbəb təsdiqləndi.')
            ->call('resolveAppeal')
            ->assertHasNoErrors();

        $case->refresh();
        $this->assertSame(CaseStatus::Final, $case->status);
        $this->assertDatabaseHas('employee_relation_case_appeals', ['case_id' => $case->id, 'status' => 'resolved', 'review_outcome' => 'annulled', 'reviewed_by' => $hr->id]);
        $this->assertContains('appeal_resolved', $subjectUser->notifications()->get()->pluck('data.kind')->all());

        app(CaseWorkflow::class)->transition($case, CaseStatus::Closed, $hr, 'Yekun');
        $this->assertSame(CaseStatus::Closed, $case->fresh()->status);
    }

    public function test_appeal_is_refused_after_the_deadline_and_for_strangers(): void
    {
        [$case, , $subjectUser, $subject] = $this->decidedCase();
        $stranger = $this->erUser([CaseAccess::SUBMIT_OWN]);
        $this->erLink($stranger, $this->erPersonnel('Kənarov', (int) $subject->structure_id));

        Livewire::actingAs($stranger)
            ->test(MyCases::class)
            ->call('startAppeal', $case->id)
            ->assertNotFound();

        Carbon::setTestNow('2026-07-12 09:00:00');

        $this->assertFalse(app(AppealService::class)->canAppeal($case->fresh(), $subjectUser, $subject->id));

        $this->expectException(ValidationException::class);
        app(AppealService::class)->submit($case->fresh(), $subjectUser, $subject->id, 'Gec verilmiş etiraz mətni.');
    }

    public function test_review_can_modify_the_decision_into_a_sanction_through_the_same_legal_checks(): void
    {
        [$case, $hr, $subjectUser, $subject] = $this->decidedCase(DecisionOutcome::VerbalWarning);
        app(AppealService::class)->submit($case, $subjectUser, $subject->id, 'Qərarla razı deyiləm, yenidən baxılsın.');
        app(AppealService::class)->startReview($case->fresh(), $hr);

        try {
            app(AppealService::class)->resolve($case->fresh(), $hr, RelationCaseAppeal::OUTCOME_MODIFIED, 'Dəyişiklik', DecisionOutcome::VerbalWarning);
            $this->fail('modified decision must differ');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('appealNewDecision', $exception->errors());
        }

        $result = app(AppealService::class)->resolve($case->fresh(), $hr, RelationCaseAppeal::OUTCOME_MODIFIED, 'Yeni faktlar üzə çıxdı.', DecisionOutcome::Reprimand);

        $this->assertFalse($result['order_follow_up']);
        $this->assertSame(DecisionOutcome::Reprimand, $result['case']->decision_outcome);
        $this->assertSame(CaseStatus::Final, $result['case']->status);
        $this->assertCount(1, $this->draftedOrders);
        $this->assertNotNull($result['case']->order_log_id);
    }

    public function test_annulling_a_sanction_flags_the_linked_order_for_cancellation(): void
    {
        [$case, $hr, $subjectUser, $subject] = $this->decidedCase(DecisionOutcome::Reprimand);
        $this->assertNotNull($case->order_log_id);

        app(AppealService::class)->submit($case, $subjectUser, $subject->id, 'Tənbeh əsassızdır, izahatım nəzərə alınmayıb.');
        app(AppealService::class)->startReview($case->fresh(), $hr);
        $result = app(AppealService::class)->resolve($case->fresh(), $hr, RelationCaseAppeal::OUTCOME_ANNULLED, 'İzahat qəbul edildi.');

        $this->assertTrue($result['order_follow_up']);
        $this->assertDatabaseHas('employee_relation_case_events', ['case_id' => $case->id, 'action' => 'order_follow_up']);
    }
}
