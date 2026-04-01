<?php

namespace App\Modules\Candidates\Support;

use App\Services\Profiles\ProfileState;

class CandidateModeResolver
{
    public const MILITARY = 'military';
    public const CIVILIAN = 'civilian';
    public const AUTO = 'auto';

    public function __construct(
        private readonly ProfileState $profileState,
        private readonly CandidateWorkflowPackResolver $workflowPackResolver,
    )
    {
    }

    public function resolve(): string
    {
        $workflowPack = strtolower($this->workflowPackResolver->resolve());

        if (in_array($workflowPack, [CandidateWorkflowPackResolver::PUBLIC, CandidateWorkflowPackResolver::PRIVATE], true)) {
            return self::CIVILIAN;
        }

        $mode = strtolower((string) config('candidates.mode', self::AUTO));

        if (in_array($mode, [self::MILITARY, self::CIVILIAN], true)) {
            return $mode;
        }

        if ($workflowPack === CandidateWorkflowPackResolver::MILITARY) {
            return self::MILITARY;
        }

        $activeProfile = strtolower($this->profileState->active());
        $map = (array) config('candidates.profile_mode_map', []);
        $resolved = strtolower((string) ($map[$activeProfile] ?? self::MILITARY));

        return in_array($resolved, [self::MILITARY, self::CIVILIAN], true)
            ? $resolved
            : self::MILITARY;
    }

    public function label(string $mode): string
    {
        $workflowPack = strtolower($this->workflowPackResolver->resolve());

        if ($mode === self::CIVILIAN && in_array($workflowPack, [CandidateWorkflowPackResolver::PUBLIC, CandidateWorkflowPackResolver::PRIVATE], true)) {
            $packLabels = (array) config('candidates.workflow_pack_labels', []);

            return (string) ($packLabels[$workflowPack] ?? ucfirst($workflowPack));
        }

        $labels = (array) config('candidates.labels', []);

        return (string) ($labels[$mode] ?? ucfirst($mode));
    }
}
