<?php

namespace App\Modules\Candidates\Support\Traits;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;
use Illuminate\Support\Facades\DB;

trait InteractsWithRecruitmentPresentation
{
    public function workflowPackResolver(): CandidateWorkflowPackResolver
    {
        return app(CandidateWorkflowPackResolver::class);
    }

    /**
     * Design-system chip tone for a requisition/opening/application status.
     */
    public function recruitmentStatusTone(?string $status): string
    {
        return match ($status) {
            'open', 'active', 'approved' => 'green',
            'draft', 'pending' => 'amber',
            'cancelled', 'rejected', 'withdrawn' => 'rose',
            'closed' => 'secondary',
            default => 'secondary',
        };
    }

    /** @var array{candidates: int, applications: int, requisitions: int, openings: int, active_candidates: int}|null */
    private ?array $recruitmentPanelCountsCache = null;

    /**
     * Counts for the recruitment contextual panel. Each one is what the page behind that
     * nav row actually lists, so the number and the screen never disagree.
     *
     * Five separate COUNT queries here, re-run on every Livewire round trip, were most of
     * the candidate list's query budget — one filter interaction paid for them four times.
     * They are one round trip now, and memoized for the life of the component instance.
     *
     * @return array{candidates: int, applications: int, requisitions: int, openings: int, active_candidates: int}
     */
    public function recruitmentPanelCounts(): array
    {
        if ($this->recruitmentPanelCountsCache !== null) {
            return $this->recruitmentPanelCountsCache;
        }

        $row = DB::query()
            ->selectSub(Candidate::query()->selectRaw('COUNT(*)'), 'candidates')
            ->selectSub(CandidateApplication::query()->selectRaw('COUNT(*)'), 'applications')
            ->selectSub(JobRequisition::query()->selectRaw('COUNT(*)'), 'requisitions')
            ->selectSub(JobOpening::query()->selectRaw('COUNT(*)'), 'openings')
            ->selectSub(
                CandidateApplication::query()->where('status', 'active')->selectRaw('COUNT(DISTINCT candidate_id)'),
                'active_candidates',
            )
            ->first();

        return $this->recruitmentPanelCountsCache = [
            'candidates' => (int) ($row->candidates ?? 0),
            'applications' => (int) ($row->applications ?? 0),
            'requisitions' => (int) ($row->requisitions ?? 0),
            'openings' => (int) ($row->openings ?? 0),
            'active_candidates' => (int) ($row->active_candidates ?? 0),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function recruitmentAvailablePacks(): array
    {
        return $this->workflowPackResolver()->available();
    }

    /**
     * @return array<int, array{id:string,label:string}>
     */
    public function recruitmentPackOptions(): array
    {
        return collect($this->recruitmentAvailablePacks())
            ->map(fn (string $pack): array => [
                'id' => $pack,
                'label' => __('candidates::recruitment.packs.'.$pack),
            ])
            ->all();
    }

    public function recruitmentPackSelectorVisible(): bool
    {
        return count($this->recruitmentAvailablePacks()) > 1;
    }

    public function defaultRecruitmentPackFilter(): string
    {
        return $this->workflowPackResolver()->isLocked()
            ? $this->workflowPackResolver()->resolve()
            : 'all';
    }

    public function normalizeRecruitmentPackFilter(?string $pack): string
    {
        $pack = strtolower((string) ($pack ?: 'all'));

        if ($this->workflowPackResolver()->isLocked()) {
            return $this->workflowPackResolver()->resolve();
        }

        return in_array($pack, array_merge(['all'], $this->recruitmentAvailablePacks()), true)
            ? $pack
            : $this->defaultRecruitmentPackFilter();
    }

    public function effectiveRecruitmentPack(?string $pack): string
    {
        return $this->normalizeRecruitmentPackFilter($pack);
    }

    /**
     * @return array<int, array{id:string,label:string}>
     */
    public function recruitmentStatusOptions(): array
    {
        return collect(['draft', 'open', 'closed', 'cancelled'])
            ->map(fn (string $status): array => [
                'id' => $status,
                'label' => __('candidates::recruitment.statuses.'.$status),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id:string,label:string}>
     */
    public function recruitmentEmploymentTypeOptions(): array
    {
        return collect(['full_time', 'part_time', 'contract', 'internship'])
            ->map(fn (string $type): array => [
                'id' => $type,
                'label' => __('candidates::recruitment.employment_types.'.$type),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id:string,label:string}>
     */
    public function recruitmentOpeningTypeOptions(): array
    {
        return collect(['standard', 'replacement', 'reserve', 'internal'])
            ->map(fn (string $type): array => [
                'id' => $type,
                'label' => __('candidates::recruitment.opening_types.'.$type),
            ])
            ->all();
    }

    public function recruitmentPackLabel(?string $pack): string
    {
        if (! $pack) {
            return '—';
        }

        return __('candidates::recruitment.packs.'.$pack);
    }

    public function recruitmentStatusLabel(?string $status): string
    {
        if (! $status) {
            return '—';
        }

        return __('candidates::recruitment.statuses.'.$status);
    }

    public function recruitmentEmploymentTypeLabel(?string $type): string
    {
        if (! $type) {
            return '—';
        }

        return __('candidates::recruitment.employment_types.'.$type);
    }

    public function recruitmentOpeningTypeLabel(?string $type): string
    {
        if (! $type) {
            return '—';
        }

        return __('candidates::recruitment.opening_types.'.$type);
    }

    public function recruitmentStageLabel(?string $stage): string
    {
        if (! $stage) {
            return '—';
        }

        return __('candidates::recruitment.stages.'.$stage);
    }
}
