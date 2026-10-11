<?php

namespace Tests\Feature\EmployeeRelations;

use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseService;
use App\Modules\EmployeeRelations\Application\Services\CaseWorkflow;
use App\Modules\EmployeeRelations\Application\Services\DecisionService;
use App\Modules\EmployeeRelations\Application\Services\ReporterIdentityService;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvidence;
use App\Modules\EmployeeRelations\Livewire\CaseDetail;
use App\Modules\EmployeeRelations\Livewire\MyCases;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\EmployeeRelations\Concerns\BuildsEmployeeRelationCases;
use Tests\TestCase;

/**
 * Məxfilik: anonim şikayətçinin kimliyi (araşdırıcıdan da gizli, icazə ilə açılır və
 * audit olunur), barəsində şikayət olunanın Şəxsi kabinetdə yalnız qərardan sonra görməsi,
 * sübut faylının özəl diskdə saxlanması və icazəli yüklənməsi.
 */
class ConfidentialityTest extends TestCase
{
    use BuildsEmployeeRelationCases;
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";

    public function test_anonymous_self_service_report_hides_the_reporter_everywhere(): void
    {
        Storage::fake('local');

        $structure = $this->erStructure('Mühasibatlıq');
        $reporter = $this->erPersonnel('Şikayətçiyev', $structure->id, 'Gizli');
        $reporterUser = $this->erUser([CaseAccess::SUBMIT_OWN], name: 'Gizli Şikayətçiyev');
        $this->erLink($reporterUser, $reporter);

        Livewire::actingAs($reporterUser)
            ->test(MyCases::class, ['personnelId' => $reporter->id])
            ->call('openForm')
            ->set('form.case_type_id', (string) $this->erTypeId('harassment'))
            ->set('form.title', 'Rəhbərin təhqiramiz davranışı')
            ->set('form.description', 'Şöbə iclasında mənə qarşı təhqiramiz ifadələr işlədilib.')
            ->set('form.subject_description', 'Şöbə müdiri')
            ->set('form.is_anonymous', true)
            ->set('attachment', UploadedFile::fake()->createWithContent('sübut.pdf', self::PDF))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Rəhbərin təhqiramiz davranışı');

        /** @var RelationCase $case */
        $case = RelationCase::query()->firstOrFail();
        $this->assertTrue($case->is_anonymous);
        $this->assertSame('self_service', $case->channel);
        $this->assertNull($case->created_by);
        $this->assertNull($case->structure_id);
        $this->assertSame($reporterUser->id, $case->reporter_user_id);
        $this->assertSame($reporter->id, $case->complainant_personnel_id);
        $this->assertArrayNotHasKey('reporter_user_id', $case->toArray());

        $this->assertSame(0, DB::table('employee_relation_case_events')->whereNotNull('actor_user_id')->count());
        $this->assertSame(0, DB::table('activity_log')->where('log_name', 'employee_relations')->whereNotNull('causer_id')->count());
        $this->assertNull(RelationCaseEvidence::query()->firstOrFail()->uploaded_by);

        // Struktursuz anonim iş yalnız «bütün strukturlar» görünürlüyündə açılır.
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], allStructures: true);
        $scopedHr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        $this->assertFalse(app(CaseAccess::class)->canView($scopedHr, $case));

        $investigator = $this->erUser([CaseAccess::SHOW], allStructures: true);
        app(CaseService::class)->assignInvestigator($case, $hr, $investigator->id);

        foreach ([$hr, $investigator] as $viewer) {
            Livewire::actingAs($viewer)
                ->test(CaseDetail::class, ['case' => $case->id])
                ->assertSee('Rəhbərin təhqiramiz davranışı')
                ->assertSee(__('employee_relations::cases.labels.anonymous'))
                ->assertDontSee('Şikayətçiyev')
                ->assertDontSee('Gizli Şikayətçiyev');
        }
    }

    public function test_revealing_an_anonymous_reporter_needs_the_permission_a_reason_and_is_audited(): void
    {
        $structure = $this->erStructure('Mühasibatlıq');
        $reporter = $this->erPersonnel('Şikayətçiyev', $structure->id);
        $reporterUser = $this->erUser([CaseAccess::SUBMIT_OWN]);
        $case = app(CaseService::class)->openBySelfService($reporterUser, $reporter->id, [
            'case_type_id' => $this->erTypeId('complaint'),
            'title' => 'Anonim şikayət',
            'description' => 'Ətraflı təsvir burada yazılıb.',
            'is_anonymous' => true,
        ]);

        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], allStructures: true);

        try {
            app(ReporterIdentityService::class)->reveal($case, $hr, 'Məhkəmə sorğusu daxil olub');
            $this->fail('reveal without the permission');
        } catch (AuthorizationException) {
            $this->assertSame(0, DB::table('activity_log')->where('event', 'reporter_revealed')->count());
        }

        $auditor = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE, CaseAccess::REVEAL], allStructures: true);

        Livewire::actingAs($auditor)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->set('revealReason', 'qısa')
            ->call('revealReporter')
            ->assertHasErrors('revealReason')
            ->set('revealReason', 'Məhkəmə sorğusu ilə kimlik tələb olunur')
            ->call('revealReporter')
            ->assertHasNoErrors()
            ->assertSee('Şikayətçiyev');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'employee_relations',
            'event' => 'reporter_revealed',
            'subject_id' => $case->id,
            'causer_id' => $auditor->id,
        ]);
        $this->assertDatabaseHas('employee_relation_case_events', [
            'case_id' => $case->id,
            'action' => 'reporter_revealed',
            'actor_user_id' => $auditor->id,
            'comment' => 'Məhkəmə sorğusu ilə kimlik tələb olunur',
        ]);
        $this->assertStringNotContainsString('Şikayətçiyev', (string) DB::table('activity_log')->where('event', 'reporter_revealed')->value('properties'));

        // Açılış bir dəfəlikdir: növbəti sorğuda kimlik yenidən gizlidir.
        Livewire::actingAs($auditor)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->assertDontSee('Şikayətçiyev');
    }

    public function test_subject_sees_the_case_in_my_hr_only_after_the_decision_is_announced(): void
    {
        $this->erFakeDrafter();

        $structure = $this->erStructure('Satış');
        $subject = $this->erPersonnel('Barəsindəov', $structure->id);
        $subjectUser = $this->erUser([]);
        $this->erLink($subjectUser, $subject);
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        $case = $this->erOpenCase($hr, $subject, ['title' => 'Müştəriyə qarşı kobudluq']);

        Livewire::actingAs($subjectUser)
            ->test(MyCases::class)
            ->assertDontSee('Müştəriyə qarşı kobudluq')
            ->assertSee(__('employee_relations::cases.my.empty.about_me'));

        app(CaseWorkflow::class)->transition($case, CaseStatus::Investigating, $hr);
        app(DecisionService::class)->decide($case->fresh(), $hr, DecisionOutcome::VerbalWarning, 'Müştəri ilə ünsiyyət qaydaları izah edildi.');

        Livewire::actingAs($subjectUser)
            ->test(MyCases::class)
            ->assertSee('Müştəriyə qarşı kobudluq')
            ->assertSee(__('employee_relations::cases.outcomes.verbal_warning'))
            ->assertSee('Müştəri ilə ünsiyyət qaydaları izah edildi.');

        // İşçi modul icazəsi olmadan işçi münasibətləri ekranını açmır.
        Livewire::actingAs($subjectUser)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->assertNotFound();
    }

    public function test_complainant_sees_only_the_general_outcome_not_the_sanction(): void
    {
        $this->erFakeDrafter();

        $structure = $this->erStructure('Satış');
        $reporter = $this->erPersonnel('Müraciətçiyev', $structure->id);
        $reporterUser = $this->erUser([CaseAccess::SUBMIT_OWN]);
        $this->erLink($reporterUser, $reporter);
        $subject = $this->erPersonnel('Pozucuyev', $structure->id);

        $case = app(CaseService::class)->openBySelfService($reporterUser, $reporter->id, [
            'case_type_id' => $this->erTypeId('disciplinary'),
            'title' => 'Növbə qaydasının pozulması',
            'description' => 'Həmkar növbəni xəbərsiz tərk edib.',
            'occurred_at' => today()->subDay()->toDateString(),
        ]);

        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id]);
        app(CaseService::class)->updateFacts($case, $hr, ['subject_personnel_id' => $subject->id]);
        app(CaseWorkflow::class)->transition($case->fresh(), CaseStatus::Investigating, $hr);
        app(DecisionService::class)->decide($case->fresh(), $hr, DecisionOutcome::Reprimand, 'Pozuntu təsdiqləndi, töhmət.');

        Livewire::actingAs($reporterUser)
            ->test(MyCases::class)
            ->assertSee('Növbə qaydasının pozulması')
            ->assertSee(__('employee_relations::cases.my.results.substantiated'))
            ->assertDontSee(__('employee_relations::cases.outcomes.reprimand'))
            ->assertDontSee('Pozuntu təsdiqləndi, töhmət.')
            ->assertDontSee('Pozucuyev');
    }

    public function test_evidence_is_stored_privately_and_downloaded_only_by_case_viewers(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $own = $this->erStructure('Öz şöbə');
        $foreign = $this->erStructure('Yad şöbə');
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$own->id]);
        $outsider = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$foreign->id]);
        $case = $this->erOpenCase($hr, $this->erPersonnel('Həsənov', $own->id));

        Livewire::actingAs($hr)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->set('evidenceKind', 'file')
            ->set('evidenceTitle', 'Saxta PDF')
            ->set('evidenceFile', UploadedFile::fake()->createWithContent('saxta.pdf', '<html><script>alert(1)</script></html>'))
            ->call('addEvidence')
            ->assertHasErrors('evidenceFile')
            ->set('evidenceTitle', 'Akt')
            ->set('evidenceFile', UploadedFile::fake()->createWithContent('akt.pdf', self::PDF))
            ->call('addEvidence')
            ->assertHasNoErrors();

        $evidence = RelationCaseEvidence::query()->firstOrFail();
        $this->assertStringStartsWith('employee-relations/evidence/'.$case->id.'/', (string) $evidence->file_path);
        Storage::disk('local')->assertExists((string) $evidence->file_path);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->actingAs($hr)
            ->get(route('employee-relations.evidence.download', ['evidence' => $evidence->id]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($outsider)
            ->get(route('employee-relations.evidence.download', ['evidence' => $evidence->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('activity_log', ['event' => 'evidence_downloaded', 'causer_id' => $hr->id]);
        $this->assertDatabaseMissing('activity_log', ['event' => 'evidence_downloaded', 'causer_id' => $outsider->id]);
    }
}
