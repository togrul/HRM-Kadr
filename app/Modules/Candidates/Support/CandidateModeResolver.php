<?php

namespace App\Modules\Candidates\Support;

use App\Services\Profiles\ProfileState;

class CandidateModeResolver
{
    public const MILITARY = 'military';
    public const CIVILIAN = 'civilian';
    public const AUTO = 'auto';

    public function __construct(private readonly ProfileState $profileState)
    {
    }

    public function resolve(): string
    {
        $workflowPack = strtolower((string) config('candidates.workflow_pack', CandidateWorkflowPackResolver::AUTO));

        if (in_array($workflowPack, [CandidateWorkflowPackResolver::PUBLIC, CandidateWorkflowPackResolver::PRIVATE], true)) {
            return self::CIVILIAN;
        }

        if ($workflowPack === CandidateWorkflowPackResolver::MILITARY) {
            return self::MILITARY;
        }

        $mode = strtolower((string) config('candidates.mode', self::AUTO));

        if (in_array($mode, [self::MILITARY, self::CIVILIAN], true)) {
            return $mode;
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
        $workflowPack = strtolower((string) config('candidates.workflow_pack', CandidateWorkflowPackResolver::AUTO));

        if ($mode === self::CIVILIAN && in_array($workflowPack, [CandidateWorkflowPackResolver::PUBLIC, CandidateWorkflowPackResolver::PRIVATE], true)) {
            $packLabels = (array) config('candidates.workflow_pack_labels', []);

            return (string) ($packLabels[$workflowPack] ?? ucfirst($workflowPack));
        }

        $labels = (array) config('candidates.labels', []);

        return (string) ($labels[$mode] ?? ucfirst($mode));
    }
}
