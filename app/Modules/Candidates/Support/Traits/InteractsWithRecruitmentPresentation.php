<?php

namespace App\Modules\Candidates\Support\Traits;

use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;

trait InteractsWithRecruitmentPresentation
{
    public function workflowPackResolver(): CandidateWorkflowPackResolver
    {
        return app(CandidateWorkflowPackResolver::class);
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
