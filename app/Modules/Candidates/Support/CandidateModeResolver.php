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
        $mode = strtolower((string) config('candidates.mode', self::AUTO));

        if (in_array($mode, [self::MILITARY, self::CIVILIAN], true)) {
            return $mode;
        }

        $activeProfile = $this->profileState->active();
        $map = (array) config('candidates.profile_mode_map', []);
        $resolved = strtolower((string) ($map[$activeProfile] ?? self::MILITARY));

        return in_array($resolved, [self::MILITARY, self::CIVILIAN], true)
            ? $resolved
            : self::MILITARY;
    }

    public function label(string $mode): string
    {
        $labels = (array) config('candidates.labels', []);

        return (string) ($labels[$mode] ?? ucfirst($mode));
    }
}

