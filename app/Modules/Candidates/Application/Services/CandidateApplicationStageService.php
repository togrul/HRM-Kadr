<?php

namespace App\Modules\Candidates\Application\Services;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CandidateApplicationStageService
{
    protected const ASSESSMENT_CHECKLISTS = [
        'private' => [
            'screening' => ['cv_match', 'salary_expectation', 'notice_period'],
            'manager_review' => ['role_scope_fit', 'team_need', 'priority_alignment'],
            'interview' => ['competency_fit', 'communication', 'culture_fit'],
            'assessment' => ['task_score', 'technical_depth', 'delivery_risk'],
            'offer' => ['offer_alignment', 'joining_plan'],
        ],
        'public' => [
            'eligibility' => ['formal_requirements', 'tenure_match', 'competition_readiness'],
            'document_review' => ['document_completeness', 'identity_verification', 'education_match'],
            'exam' => ['written_score', 'minimum_threshold', 'exam_protocol'],
            'commission_interview' => ['commission_score', 'ethics_fit', 'public_service_fit'],
            'ranking' => ['ranking_formula', 'tie_break_rules', 'reserve_order'],
            'reserve' => ['reserve_validity', 'reserve_priority'],
        ],
        'military' => [
            'screening' => ['service_fit', 'basic_documents', 'initial_suitability'],
            'aptitude_test' => ['aptitude_score', 'minimum_threshold', 'behavioral_fit'],
            'physical_test' => ['physical_norms', 'stamina_result', 'fitness_protocol'],
            'medical_board' => ['medical_board_result', 'fit_for_service', 'medical_restrictions'],
            'security_research' => ['background_result', 'risk_flags', 'integrity_summary'],
            'commission' => ['commission_vote', 'final_suitability', 'command_alignment'],
            'appointment_ready' => ['appointment_clearance', 'start_readiness'],
        ],
    ];

    protected const DOCUMENT_CHECKLISTS = [
        'private' => [
            'applied' => ['cv', 'portfolio'],
            'interview' => ['interview_notes', 'reference_contacts'],
            'assessment' => ['task_file', 'assessment_sheet'],
            'offer' => ['salary_offer', 'offer_confirmation'],
        ],
        'public' => [
            'eligibility' => ['application_form', 'identity_document'],
            'document_review' => ['education_documents', 'service_record', 'competition_documents'],
            'exam' => ['exam_sheet', 'attendance_protocol'],
            'ranking' => ['ranking_protocol', 'commission_minutes'],
            'reserve' => ['reserve_order_document'],
        ],
        'military' => [
            'screening' => ['identity_document', 'military_record'],
            'aptitude_test' => ['test_protocol', 'score_sheet'],
            'physical_test' => ['fitness_protocol', 'medical_clearance'],
            'medical_board' => ['medical_board_minutes'],
            'security_research' => ['security_clearance_report'],
            'commission' => ['commission_minutes'],
            'appointment_ready' => ['appointment_clearance', 'start_order_draft'],
        ],
    ];

    public function artifactService(): CandidateApplicationStageArtifactService
    {
        return app(CandidateApplicationStageArtifactService::class);
    }

    protected function stage(string $key, bool $terminal = false): array
    {
        return [
            'key' => $key,
            'label' => __('candidates::recruitment.stages.'.$key),
            'terminal' => $terminal,
        ];
    }

    /**
     * @return array<int, array{key:string,label:string,terminal?:bool}>
     */
    public function stagesForPack(string $pack): array
    {
        return match (strtolower($pack)) {
            'private' => [
                $this->stage('applied'),
                $this->stage('screening'),
                $this->stage('manager_review'),
                $this->stage('interview'),
                $this->stage('assessment'),
                $this->stage('offer'),
                $this->stage('hired', true),
                $this->stage('rejected', true),
                $this->stage('withdrawn', true),
            ],
            'public' => [
                $this->stage('applied'),
                $this->stage('eligibility'),
                $this->stage('document_review'),
                $this->stage('exam'),
                $this->stage('commission_interview'),
                $this->stage('ranking'),
                $this->stage('reserve'),
                $this->stage('appointed', true),
                $this->stage('rejected', true),
                $this->stage('withdrawn', true),
            ],
            default => [
                $this->stage('applied'),
                $this->stage('screening'),
                $this->stage('aptitude_test'),
                $this->stage('physical_test'),
                $this->stage('medical_board'),
                $this->stage('security_research'),
                $this->stage('commission'),
                $this->stage('appointment_ready'),
                $this->stage('hired', true),
                $this->stage('rejected', true),
                $this->stage('withdrawn', true),
            ],
        };
    }

    public function createInitialApplication(Candidate $candidate, JobOpening $opening, array $attributes = []): CandidateApplication
    {
        $firstStage = $this->stagesForPack((string) $opening->profile_pack)[0]['key'] ?? 'applied';

        return DB::transaction(function () use ($candidate, $opening, $attributes, $firstStage): CandidateApplication {
            $application = CandidateApplication::query()->create([
                'candidate_id' => $candidate->id,
                'job_opening_id' => $opening->id,
                'candidate_source_id' => $attributes['candidate_source_id'] ?? null,
                'assigned_recruiter_id' => $attributes['assigned_recruiter_id'] ?? null,
                'current_stage' => $attributes['current_stage'] ?? $firstStage,
                'status' => $attributes['status'] ?? 'active',
                'applied_at' => $attributes['applied_at'] ?? now(),
                'moved_at' => $attributes['moved_at'] ?? now(),
                'note' => $attributes['note'] ?? null,
            ]);

            $application->stageEvents()->create([
                'stage_key' => $application->current_stage,
                'action' => 'created',
                'actor_id' => $attributes['actor_id'] ?? auth()->id(),
                'occurred_at' => $attributes['occurred_at'] ?? now(),
                'payload' => $attributes['payload'] ?? [
                    'source' => 'initial_application',
                    'audit' => [
                        'from_stage' => null,
                        'to_stage' => $application->current_stage,
                        'assessment_total' => 0,
                        'assessment_passed' => 0,
                        'assessment_failed' => 0,
                        'document_total' => 0,
                        'document_provided' => 0,
                        'profile_field_keys' => [],
                    ],
                ],
            ]);

            return $application;
        });
    }

    public function moveToStage(CandidateApplication $application, string $toStage, array $context = []): CandidateApplication
    {
        $opening = $application->opening()->select('id', 'profile_pack')->firstOrFail();
        $allowed = collect($this->stagesForPack((string) $opening->profile_pack))->pluck('key')->all();
        $fromStage = $application->current_stage;

        if (! in_array($toStage, $allowed, true)) {
            throw new InvalidArgumentException("Unsupported stage [{$toStage}] for pack [{$opening->profile_pack}].");
        }

        return DB::transaction(function () use ($application, $toStage, $context, $fromStage): CandidateApplication {
            $updates = [
                'current_stage' => $toStage,
                'moved_at' => $context['occurred_at'] ?? now(),
            ];

            if ($toStage === 'rejected') {
                $updates['status'] = 'rejected';
                $updates['final_decision'] = $context['final_decision'] ?? 'rejected';
                $updates['rejected_at'] = $context['occurred_at'] ?? now();
                $updates['rejection_reason_id'] = $context['rejection_reason_id'] ?? $application->rejection_reason_id;
            } elseif ($toStage === 'withdrawn') {
                $updates['status'] = 'withdrawn';
                $updates['final_decision'] = $context['final_decision'] ?? 'withdrawn';
                $updates['withdrawn_at'] = $context['occurred_at'] ?? now();
            } elseif (in_array($toStage, ['hired', 'appointed'], true)) {
                $updates['status'] = 'closed';
                $updates['final_decision'] = $context['final_decision'] ?? $toStage;
                $updates['hired_at'] = $context['occurred_at'] ?? now();
            }

            $application->update($updates);

            $this->artifactService()->syncForStage(
                $application,
                $toStage,
                $context['assessment_items'] ?? [],
                $context['document_items'] ?? [],
                $context['profile_fields'] ?? [],
                $context['actor_id'] ?? auth()->id(),
                $context['occurred_at'] ?? now(),
            );

            $auditPayload = $this->buildAuditPayload(
                fromStage: $fromStage,
                toStage: $toStage,
                assessmentItems: $context['assessment_items'] ?? [],
                documentItems: $context['document_items'] ?? [],
                profileFields: $context['profile_fields'] ?? [],
                context: $context,
            );

            $payload = is_array($context['payload'] ?? null) ? $context['payload'] : [];
            $payload['audit'] = $auditPayload;

            $application->stageEvents()->create([
                'stage_key' => $toStage,
                'action' => $context['action'] ?? 'moved',
                'decision' => $context['decision'] ?? null,
                'score' => $context['score'] ?? null,
                'actor_id' => $context['actor_id'] ?? auth()->id(),
                'occurred_at' => $context['occurred_at'] ?? now(),
                'payload' => $payload,
                'note' => $context['note'] ?? null,
            ]);

            if (in_array($toStage, ['hired', 'appointed'], true)) {
                app(CandidateHireConversionService::class)->convert($application, $context);
            }

            return $application->fresh(['opening', 'personnel', 'stageEvents', 'assessments', 'documentChecks']);
        });
    }

    /**
     * @param  array<string, array{status?: mixed, note?: mixed}>  $assessmentItems
     * @param  array<string, array{is_provided?: mixed, note?: mixed}>  $documentItems
     * @param  array<string, mixed>  $profileFields
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function buildAuditPayload(
        ?string $fromStage,
        string $toStage,
        array $assessmentItems,
        array $documentItems,
        array $profileFields,
        array $context,
    ): array {
        $profileFieldKeys = collect($profileFields)
            ->filter(fn (mixed $value) => $value !== null && $value !== '')
            ->keys()
            ->values()
            ->all();

        return [
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'assessment_total' => count($assessmentItems),
            'assessment_passed' => collect($assessmentItems)->where('status', 'passed')->count(),
            'assessment_failed' => collect($assessmentItems)->where('status', 'failed')->count(),
            'document_total' => count($documentItems),
            'document_provided' => collect($documentItems)->filter(fn (array $item) => (bool) ($item['is_provided'] ?? false))->count(),
            'profile_field_keys' => $profileFieldKeys,
            'decision' => $context['decision'] ?? null,
            'final_decision' => $context['final_decision'] ?? null,
            'rejection_reason_id' => $context['rejection_reason_id'] ?? null,
        ];
    }

    /**
     * @return array<int, array{key:string,label:string,count:int,terminal:bool}>
     */
    public function stageSummaryForOpening(JobOpening $opening): array
    {
        $counts = CandidateApplication::query()
            ->selectRaw('current_stage, COUNT(*) as aggregate')
            ->where('job_opening_id', $opening->id)
            ->groupBy('current_stage')
            ->pluck('aggregate', 'current_stage');

        return $this->stageSummaryForCounts((string) $opening->profile_pack, $counts);
    }

    /**
     * @param  Collection<int|string, mixed>|array<string|int, mixed>  $counts
     * @return array<int, array{key:string,label:string,count:int,terminal:bool}>
     */
    public function stageSummaryForCounts(string $pack, Collection|array $counts): array
    {
        $counts = $counts instanceof Collection ? $counts : collect($counts);

        return collect($this->stagesForPack($pack))
            ->map(fn (array $stage): array => [
                'key' => $stage['key'],
                'label' => $stage['label'],
                'count' => (int) ($counts[$stage['key']] ?? 0),
                'terminal' => (bool) ($stage['terminal'] ?? false),
            ])
            ->all();
    }

    /**
     * @return array<int, array{key:string,label:string,count:int,terminal:bool}>
     */
    public function stageSummaryForQuery(Builder $query, string $pack): array
    {
        $counts = (clone $query)
            ->selectRaw('current_stage, COUNT(*) as aggregate')
            ->groupBy('current_stage')
            ->pluck('aggregate', 'current_stage');

        return $this->stageSummaryForCounts($pack, $counts);
    }

    /**
     * @return array<int, string>
     */
    public function assessmentChecklistForStage(string $pack, string $stage): array
    {
        $pack = strtolower($pack);

        return self::ASSESSMENT_CHECKLISTS[$pack][$stage] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function documentChecklistForStage(string $pack, string $stage): array
    {
        $pack = strtolower($pack);

        return self::DOCUMENT_CHECKLISTS[$pack][$stage] ?? [];
    }
}
