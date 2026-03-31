<?php

namespace App\Modules\Candidates\Support;

use App\Services\Profiles\ProfileState;

class CandidateWorkflowPackResolver
{
    public const MILITARY = 'military';
    public const PUBLIC = 'public';
    public const PRIVATE = 'private';
    public const AUTO = 'auto';

    public function __construct(private readonly ProfileState $profileState)
    {
    }

    public function resolve(): string
    {
        $configured = strtolower((string) config('candidates.workflow_pack', self::AUTO));

        if (in_array($configured, [self::MILITARY, self::PUBLIC, self::PRIVATE], true)) {
            return $configured;
        }

        $activeProfile = strtolower($this->profileState->active());
        $map = (array) config('candidates.workflow_pack_map', []);
        $resolved = strtolower((string) ($map[$activeProfile] ?? self::MILITARY));

        return in_array($resolved, [self::MILITARY, self::PUBLIC, self::PRIVATE], true)
            ? $resolved
            : self::MILITARY;
    }

    /**
     * @return array<int, string>
     */
    public function available(): array
    {
        $configured = config('candidates.workflow_visible_packs', 'auto');
        $valid = [self::MILITARY, self::PUBLIC, self::PRIVATE];
        $resolved = $this->resolve();

        if (is_array($configured)) {
            $packs = array_values(array_unique(array_filter(
                array_map(fn ($pack) => strtolower((string) $pack), $configured),
                fn ($pack) => in_array($pack, $valid, true)
            )));

            return $packs !== [] ? $packs : [$resolved];
        }

        if (is_string($configured) && strtolower($configured) !== self::AUTO) {
            $packs = array_values(array_unique(array_filter(
                array_map('trim', explode(',', strtolower($configured))),
                fn ($pack) => in_array($pack, $valid, true)
            )));

            return $packs !== [] ? $packs : [$resolved];
        }

        $workflowPack = strtolower((string) config('candidates.workflow_pack', self::AUTO));

        if (in_array($workflowPack, $valid, true)) {
            return [$resolved];
        }

        $activeProfile = strtolower($this->profileState->active());
        $map = (array) config('candidates.workflow_visible_pack_map', []);
        $mapped = $map[$activeProfile] ?? $map['default'] ?? [$resolved];
        $packs = is_array($mapped) ? $mapped : [$mapped];

        $packs = array_values(array_unique(array_filter(
            array_map(fn ($pack) => strtolower((string) $pack), $packs),
            fn ($pack) => in_array($pack, $valid, true)
        )));

        return $packs !== [] ? $packs : [$resolved];
    }

    public function isLocked(): bool
    {
        return count($this->available()) === 1;
    }

    public function label(string $pack): string
    {
        $labels = (array) config('candidates.workflow_pack_labels', []);

        return (string) ($labels[$pack] ?? ucfirst($pack));
    }
}
