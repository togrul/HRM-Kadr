<?php

namespace App\Modules\Candidates\Application\Services;

use App\Models\CandidateApplication;
use App\Models\CandidateInterview;
use App\Models\CandidateOffer;
use App\Models\CandidateStageEvent;
use App\Models\CandidateTalentPoolEntry;
use App\Models\JobRequisition;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CandidateAtsCompletionService
{
    public function submitRequisition(JobRequisition $requisition, ?int $actorId = null, ?string $note = null): JobRequisition
    {
        $requisition->forceFill([
            'approval_status' => 'pending',
            'status' => $requisition->status === 'draft' ? 'pending_approval' : $requisition->status,
            'approval_note' => $note,
            'requested_by' => $requisition->requested_by ?: $actorId,
        ])->save();

        return $requisition->refresh();
    }

    public function approveRequisition(JobRequisition $requisition, int $actorId, ?string $note = null): JobRequisition
    {
        $requisition->forceFill([
            'approval_status' => 'approved',
            'status' => 'approved',
            'approved_by' => $actorId,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'approval_note' => $note,
        ])->save();

        return $requisition->refresh();
    }

    public function rejectRequisition(JobRequisition $requisition, int $actorId, ?string $note = null): JobRequisition
    {
        $requisition->forceFill([
            'approval_status' => 'rejected',
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => $actorId,
            'rejected_at' => now(),
            'approval_note' => $note,
        ])->save();

        return $requisition->refresh();
    }

    public function scheduleInterview(CandidateApplication $application, array $payload): CandidateInterview
    {
        return DB::transaction(function () use ($application, $payload): CandidateInterview {
            $interview = CandidateInterview::query()->create([
                'candidate_application_id' => $application->id,
                'stage_key' => $payload['stage_key'] ?? $application->current_stage,
                'interviewer_id' => $payload['interviewer_id'] ?? null,
                'scheduled_at' => isset($payload['scheduled_at']) ? Carbon::parse($payload['scheduled_at']) : null,
                'duration_minutes' => (int) ($payload['duration_minutes'] ?? 45),
                'location' => $payload['location'] ?? null,
                'status' => $payload['status'] ?? 'scheduled',
                'notes' => $payload['notes'] ?? null,
                'created_by' => $payload['created_by'] ?? null,
            ]);

            $this->stageEvent($application, 'interview_scheduled', [
                'stage_key' => $interview->stage_key,
                'actor_id' => $payload['created_by'] ?? null,
                'payload' => ['interview_id' => $interview->id],
                'note' => $payload['notes'] ?? null,
            ]);

            return $interview;
        });
    }

    public function submitScorecard(CandidateInterview $interview, array $items, ?int $reviewerId = null, ?string $note = null): CandidateInterview
    {
        return DB::transaction(function () use ($interview, $items, $reviewerId, $note): CandidateInterview {
            foreach ($items as $item) {
                $interview->scorecards()->updateOrCreate(
                    [
                        'reviewer_id' => $reviewerId,
                        'criterion' => (string) $item['criterion'],
                    ],
                    [
                        'score' => max(0, min(100, (int) ($item['score'] ?? 0))),
                        'comment' => $item['comment'] ?? null,
                    ]
                );
            }

            $average = (float) $interview->scorecards()->avg('score');
            $interview->forceFill([
                'score' => round($average, 2),
                'status' => 'completed',
                'notes' => $note ?: $interview->notes,
            ])->save();

            $this->stageEvent($interview->application, 'scorecard_submitted', [
                'stage_key' => $interview->stage_key ?: $interview->application->current_stage,
                'actor_id' => $reviewerId,
                'score' => round($average, 2),
                'payload' => ['interview_id' => $interview->id],
                'note' => $note,
            ]);

            return $interview->refresh();
        });
    }

    public function updateInterviewStatus(CandidateInterview $interview, string $status, ?int $actorId = null, ?string $note = null): CandidateInterview
    {
        abort_unless(in_array($status, ['scheduled', 'completed', 'cancelled'], true), 422);

        return DB::transaction(function () use ($interview, $status, $actorId, $note): CandidateInterview {
            $interview->forceFill([
                'status' => $status,
                'notes' => $note ?: $interview->notes,
            ])->save();

            $this->stageEvent($interview->application, 'interview_'.$status, [
                'stage_key' => $interview->stage_key ?: $interview->application->current_stage,
                'actor_id' => $actorId,
                'payload' => ['interview_id' => $interview->id, 'status' => $status],
                'note' => $note,
            ]);

            return $interview->refresh();
        });
    }

    public function createOffer(CandidateApplication $application, array $payload): CandidateOffer
    {
        return DB::transaction(function () use ($application, $payload): CandidateOffer {
            $offer = CandidateOffer::query()->create([
                'candidate_application_id' => $application->id,
                'salary_amount' => $payload['salary_amount'] ?? null,
                'currency' => $payload['currency'] ?? 'AZN',
                'start_date' => $payload['start_date'] ?? null,
                'expires_at' => $payload['expires_at'] ?? null,
                'status' => $payload['status'] ?? 'draft',
                'terms' => $payload['terms'] ?? null,
                'created_by' => $payload['created_by'] ?? null,
            ]);

            $this->stageEvent($application, 'offer_created', [
                'stage_key' => $application->current_stage,
                'actor_id' => $payload['created_by'] ?? null,
                'payload' => ['offer_id' => $offer->id, 'status' => $offer->status],
                'note' => $payload['terms'] ?? null,
            ]);

            return $offer;
        });
    }

    public function updateOfferStatus(CandidateOffer $offer, string $status, ?int $actorId = null, ?string $note = null): CandidateOffer
    {
        return DB::transaction(function () use ($offer, $status, $actorId, $note): CandidateOffer {
            $offer->forceFill(['status' => $status])->save();

            $this->stageEvent($offer->application, 'offer_'.$status, [
                'stage_key' => $offer->application->current_stage,
                'actor_id' => $actorId,
                'payload' => ['offer_id' => $offer->id, 'status' => $status],
                'note' => $note,
            ]);

            return $offer->refresh();
        });
    }

    public function addToTalentPool(CandidateApplication $application, array $payload): CandidateTalentPoolEntry
    {
        return DB::transaction(function () use ($application, $payload): CandidateTalentPoolEntry {
            $entry = CandidateTalentPoolEntry::query()->updateOrCreate(
                [
                    'candidate_id' => $application->candidate_id,
                    'pool_name' => $payload['pool_name'] ?? 'default',
                ],
                [
                    'candidate_application_id' => $application->id,
                    'status' => $payload['status'] ?? 'active',
                    'valid_until' => $payload['valid_until'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                    'created_by' => $payload['created_by'] ?? null,
                ]
            );

            $this->stageEvent($application, 'talent_pool_added', [
                'stage_key' => $application->current_stage,
                'actor_id' => $payload['created_by'] ?? null,
                'payload' => ['talent_pool_entry_id' => $entry->id, 'pool_name' => $entry->pool_name],
                'note' => $payload['notes'] ?? null,
            ]);

            return $entry;
        });
    }

    public function requisitionAging(int $warningDays = 14): array
    {
        if (! Schema::hasTable('job_requisitions') || ! Schema::hasColumn('job_requisitions', 'approval_status')) {
            return [
                'warning_days' => $warningDays,
                'total_open' => 0,
                'stale' => 0,
                'rows' => collect(),
            ];
        }

        $rows = JobRequisition::query()
            ->whereIn('approval_status', ['draft', 'pending'])
            ->select(['id', 'title', 'status', 'approval_status', 'created_at', 'owner_id', 'requested_by'])
            ->oldest('created_at')
            ->get()
            ->map(fn (JobRequisition $requisition): array => [
                'id' => $requisition->id,
                'title' => $requisition->title,
                'status' => $requisition->status,
                'approval_status' => $requisition->approval_status ?: $requisition->status,
                'age_days' => (int) $requisition->created_at->diffInDays(now()),
                'is_stale' => $requisition->created_at->lte(now()->subDays($warningDays)),
                'owner_id' => $requisition->owner_id,
                'requested_by' => $requisition->requested_by,
            ]);

        return [
            'warning_days' => $warningDays,
            'total_open' => $rows->count(),
            'stale' => $rows->where('is_stale', true)->count(),
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{stage_key?:string|null, actor_id?:int|null, score?:float|int|null, payload?:array<string,mixed>, note?:string|null}  $payload
     */
    private function stageEvent(CandidateApplication $application, string $action, array $payload): void
    {
        CandidateStageEvent::query()->create([
            'candidate_application_id' => $application->id,
            'stage_key' => ($payload['stage_key'] ?? null) ?: $application->current_stage,
            'action' => $action,
            'decision' => null,
            'score' => $payload['score'] ?? null,
            'actor_id' => $payload['actor_id'] ?? null,
            'payload' => $payload['payload'] ?? [],
            'occurred_at' => now(),
            'note' => $payload['note'] ?? null,
        ]);
    }
}
