<?php

namespace App\Modules\Candidates\Application\Services;

use App\Models\CandidateApplication;

class CandidateApplicationStageArtifactService
{
    protected const ASSESSMENT_STATUSES = ['pending', 'passed', 'failed', 'waived'];

    protected const PROFILE_FIELD_DEFINITIONS = [
        'private' => [
            'screening' => [
                ['key' => 'salary_expectation', 'type' => 'number', 'label' => 'candidate_expectation_salary'],
                ['key' => 'notice_period_days', 'type' => 'number', 'label' => 'candidate_notice_period_days'],
            ],
            'interview' => [
                ['key' => 'interview_type', 'type' => 'select', 'label' => 'interview_type', 'options' => ['online', 'onsite', 'panel']],
                ['key' => 'recommendation', 'type' => 'select', 'label' => 'recommendation', 'options' => ['strong_yes', 'yes', 'hold', 'no']],
            ],
            'offer' => [
                ['key' => 'expected_joining_date', 'type' => 'date', 'label' => 'expected_joining_date'],
                ['key' => 'offer_status', 'type' => 'select', 'label' => 'offer_status', 'options' => ['prepared', 'shared', 'accepted', 'declined']],
            ],
        ],
        'public' => [
            'eligibility' => [
                ['key' => 'competition_type', 'type' => 'select', 'label' => 'competition_type', 'options' => ['internal', 'open', 'targeted']],
                ['key' => 'eligibility_basis', 'type' => 'text', 'label' => 'eligibility_basis'],
            ],
            'exam' => [
                ['key' => 'exam_score_sheet_no', 'type' => 'text', 'label' => 'exam_score_sheet_no'],
                ['key' => 'exam_location', 'type' => 'text', 'label' => 'exam_location'],
            ],
            'ranking' => [
                ['key' => 'ranking_order', 'type' => 'number', 'label' => 'ranking_order'],
                ['key' => 'reserve_window_days', 'type' => 'number', 'label' => 'reserve_window_days'],
            ],
        ],
        'military' => [
            'aptitude_test' => [
                ['key' => 'aptitude_score', 'type' => 'number', 'label' => 'aptitude_score'],
                ['key' => 'aptitude_protocol_no', 'type' => 'text', 'label' => 'aptitude_protocol_no'],
            ],
            'physical_test' => [
                ['key' => 'physical_score', 'type' => 'number', 'label' => 'physical_score'],
                ['key' => 'physical_protocol_no', 'type' => 'text', 'label' => 'physical_protocol_no'],
            ],
            'security_research' => [
                ['key' => 'clearance_reference', 'type' => 'text', 'label' => 'clearance_reference'],
                ['key' => 'risk_summary', 'type' => 'text', 'label' => 'risk_summary'],
            ],
            'commission' => [
                ['key' => 'commission_protocol_no', 'type' => 'text', 'label' => 'commission_protocol_no'],
                ['key' => 'commission_recommendation', 'type' => 'select', 'label' => 'commission_recommendation', 'options' => ['fit', 'hold', 'not_fit']],
            ],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public function assessmentStatuses(): array
    {
        return self::ASSESSMENT_STATUSES;
    }

    /**
     * @return array<int, array{key:string,type:string,label:string,options?:array<int,string>}>
     */
    public function profileFieldDefinitionsForStage(string $pack, string $stage): array
    {
        $pack = strtolower($pack);

        return self::PROFILE_FIELD_DEFINITIONS[$pack][$stage] ?? [];
    }

    /**
     * @param  array<int, string>  $assessmentChecklist
     * @param  array<int, string>  $documentChecklist
     * @return array{assessment_items: array<string, array{status:string,note:string}>, document_items: array<string, array{is_provided:bool,note:string}>}
     */
    public function hydrateStageFormState(
        CandidateApplication $application,
        string $stage,
        array $assessmentChecklist,
        array $documentChecklist,
    ): array {
        $assessmentRecords = $application->assessments
            ->where('stage_key', $stage)
            ->keyBy('assessment_key');

        $documentRecords = $application->documentChecks
            ->where('stage_key', $stage)
            ->keyBy('document_key');

        $assessmentItems = [];
        foreach ($assessmentChecklist as $item) {
            $record = $assessmentRecords->get($item);
            $assessmentItems[$item] = [
                'status' => in_array((string) $record?->status, self::ASSESSMENT_STATUSES, true)
                    ? (string) $record->status
                    : 'pending',
                'note' => (string) ($record?->note ?? ''),
            ];
        }

        $documentItems = [];
        foreach ($documentChecklist as $item) {
            $record = $documentRecords->get($item);
            $documentItems[$item] = [
                'is_provided' => (bool) ($record?->is_provided ?? false),
                'note' => (string) ($record?->note ?? ''),
            ];
        }

        return [
            'assessment_items' => $assessmentItems,
            'document_items' => $documentItems,
        ];
    }

    /**
     * @param  array<string, array{status?: mixed, note?: mixed}>  $assessmentItems
     * @param  array<string, array{is_provided?: mixed, note?: mixed}>  $documentItems
     */
    public function syncForStage(
        CandidateApplication $application,
        string $stage,
        array $assessmentItems,
        array $documentItems,
        array $profileFields = [],
        ?int $actorId = null,
        $recordedAt = null,
    ): void {
        foreach ($assessmentItems as $key => $item) {
            $application->assessments()->updateOrCreate(
                [
                    'stage_key' => $stage,
                    'assessment_key' => $key,
                ],
                [
                    'status' => $this->normalizeAssessmentStatus($item['status'] ?? null),
                    'note' => $this->normalizeNullableString($item['note'] ?? null),
                    'actor_id' => $actorId,
                    'recorded_at' => $recordedAt,
                ]
            );
        }

        foreach ($documentItems as $key => $item) {
            $application->documentChecks()->updateOrCreate(
                [
                    'stage_key' => $stage,
                    'document_key' => $key,
                ],
                [
                    'is_provided' => (bool) ($item['is_provided'] ?? false),
                    'note' => $this->normalizeNullableString($item['note'] ?? null),
                    'actor_id' => $actorId,
                    'recorded_at' => $recordedAt,
                ]
            );
        }

        if ($profileFields !== []) {
            $application->stageProfiles()->updateOrCreate(
                [
                    'stage_key' => $stage,
                ],
                [
                    'profile_pack' => $application->opening?->profile_pack ?? 'military',
                    'payload' => $profileFields,
                    'actor_id' => $actorId,
                    'recorded_at' => $recordedAt,
                ]
            );
        }
    }

    /**
     * @param  array<int, array{key:string,type:string,label:string,options?:array<int,string>}>  $definitions
     * @return array<string, mixed>
     */
    public function hydrateProfileFieldState(CandidateApplication $application, string $stage, array $definitions): array
    {
        $profile = $application->stageProfiles
            ->firstWhere('stage_key', $stage);

        $payload = is_array($profile?->payload) ? $profile->payload : [];
        $state = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $state[$key] = $payload[$key] ?? null;
        }

        return $state;
    }

    protected function normalizeAssessmentStatus(mixed $status): string
    {
        $status = strtolower((string) $status);

        return in_array($status, self::ASSESSMENT_STATUSES, true)
            ? $status
            : 'pending';
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
