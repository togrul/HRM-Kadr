<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\SuccessionCandidate;
use App\Models\SuccessionPlan;
use App\Models\TalentAssessment;
use App\Models\TalentPool;
use App\Models\TalentPoolMember;
use Illuminate\Support\Collection;

/**
 * Succession planning + talent: the 9-box grid (performance × potential), succession
 * plans for critical seats with ranked successors, and talent pools (HiPo / successor
 * benches). The 9-box grid reuses each person's talent assessment for the selected cycle.
 */
class SuccessionService
{
    /**
     * 3×3 grid, top row = high potential, left column = low performance.
     *
     * @return array<int, array<string, mixed>>
     */
    public function nineBox(?int $cycleId): array
    {
        $assessments = TalentAssessment::query()
            ->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId), fn ($q) => $q->whereNull('performance_cycle_id'))
            ->with('personnel:id,surname,name,patronymic')
            ->get();

        $byBox = $assessments->groupBy(fn (TalentAssessment $a): int => $a->box);

        $boxes = [];
        for ($potential = 3; $potential >= 1; $potential--) {
            for ($performance = 1; $performance <= 3; $performance++) {
                $index = ($potential - 1) * 3 + $performance;
                $boxes[] = [
                    'index' => $index,
                    'performance' => $performance,
                    'potential' => $potential,
                    'people' => $byBox->get($index, collect())
                        ->map(fn (TalentAssessment $a): array => [
                            'assessment_id' => $a->id,
                            'personnel_id' => (int) $a->personnel_id,
                            'name' => trim(($a->personnel->surname ?? '').' '.($a->personnel->name ?? '')),
                        ])
                        ->values()
                        ->all(),
                ];
            }
        }

        return $boxes;
    }

    public function upsertAssessment(int $personnelId, ?int $cycleId, int $performance, int $potential, ?string $note = null): TalentAssessment
    {
        return TalentAssessment::updateOrCreate(
            ['personnel_id' => $personnelId, 'performance_cycle_id' => $cycleId],
            [
                'performance_level' => max(1, min(3, $performance)),
                'potential_level' => max(1, min(3, $potential)),
                'note' => $note,
                'assessed_by' => auth()->id(),
            ],
        );
    }

    public function removeAssessment(int $assessmentId): void
    {
        TalentAssessment::whereKey($assessmentId)->delete();
    }

    // ── succession plans ──────────────────────────────────────────────

    public function plans(): Collection
    {
        return SuccessionPlan::query()
            ->with([
                'incumbent:id,surname,name,patronymic',
                'position:id,name',
                'candidates.personnel:id,surname,name,patronymic',
            ])
            ->latest()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPlan(array $data): SuccessionPlan
    {
        return SuccessionPlan::create([
            'role_title' => $data['role_title'],
            'position_id' => $data['position_id'] ?? null,
            'structure_id' => $data['structure_id'] ?? null,
            'incumbent_personnel_id' => $data['incumbent_personnel_id'] ?? null,
            'risk_of_loss' => in_array($data['risk_of_loss'] ?? '', SuccessionPlan::RISK_LEVELS, true) ? $data['risk_of_loss'] : 'medium',
            'impact_of_loss' => in_array($data['impact_of_loss'] ?? '', SuccessionPlan::RISK_LEVELS, true) ? $data['impact_of_loss'] : 'high',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    public function deletePlan(int $planId): void
    {
        SuccessionPlan::whereKey($planId)->delete();
    }

    public function addCandidate(int $planId, int $personnelId, string $readiness): SuccessionCandidate
    {
        return SuccessionCandidate::firstOrCreate(
            ['succession_plan_id' => $planId, 'personnel_id' => $personnelId],
            [
                'readiness' => in_array($readiness, SuccessionCandidate::READINESS, true) ? $readiness : '1_2_years',
                'sort_order' => SuccessionCandidate::where('succession_plan_id', $planId)->max('sort_order') + 1,
            ],
        );
    }

    public function setCandidateReadiness(int $candidateId, string $readiness): void
    {
        if (! in_array($readiness, SuccessionCandidate::READINESS, true)) {
            return;
        }
        SuccessionCandidate::whereKey($candidateId)->update(['readiness' => $readiness]);
    }

    public function removeCandidate(int $candidateId): void
    {
        SuccessionCandidate::whereKey($candidateId)->delete();
    }

    // ── talent pools ──────────────────────────────────────────────────

    public function pools(): Collection
    {
        return TalentPool::query()
            ->with('members.personnel:id,surname,name,patronymic')
            ->latest()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPool(array $data): TalentPool
    {
        return TalentPool::create([
            'name' => $data['name'],
            'pool_type' => in_array($data['pool_type'] ?? '', TalentPool::TYPES, true) ? $data['pool_type'] : 'hipo',
            'description' => $data['description'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    public function deletePool(int $poolId): void
    {
        TalentPool::whereKey($poolId)->delete();
    }

    public function addMember(int $poolId, int $personnelId): TalentPoolMember
    {
        return TalentPoolMember::firstOrCreate(
            ['talent_pool_id' => $poolId, 'personnel_id' => $personnelId],
        );
    }

    public function removeMember(int $memberId): void
    {
        TalentPoolMember::whereKey($memberId)->delete();
    }

    /**
     * @return array{assessed:int, top_talent:int, plans:int, plans_no_ready:int}
     */
    public function summary(?int $cycleId): array
    {
        $assessments = TalentAssessment::query()
            ->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId), fn ($q) => $q->whereNull('performance_cycle_id'))
            ->get(['performance_level', 'potential_level']);

        $topTalent = $assessments->filter(fn (TalentAssessment $a): bool => $a->performance_level >= 3 && $a->potential_level >= 3)->count();

        $plans = SuccessionPlan::query()->withCount(['candidates as ready_now_count' => fn ($q) => $q->where('readiness', 'ready_now')])->get();

        return [
            'assessed' => $assessments->count(),
            'top_talent' => $topTalent,
            'plans' => $plans->count(),
            'plans_no_ready' => $plans->where('ready_now_count', 0)->count(),
        ];
    }
}
