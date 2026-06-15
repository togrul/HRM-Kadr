<?php

namespace App\Modules\Attendance\Support;

/**
 * Pure presentation logic for puantaj (timesheet) leave cells: legend codes/icons,
 * family grouping keys/labels and the deterministic colour tone for a leave type.
 *
 * Extracted verbatim from PuantajGrid so the rendering component stays focused on
 * data orchestration and this mapping can be unit-tested in isolation.
 */
class LeaveLegendPresenter
{
    public function resolveLeaveLegendCode(string $leaveTypeCode, string $leaveTypeName, string $absenceCode): string
    {
        if ($leaveTypeCode !== '') {
            return strtoupper($leaveTypeCode);
        }

        if ($absenceCode !== '') {
            return strtoupper($absenceCode);
        }

        $trimmed = trim($leaveTypeName);
        if ($trimmed === '') {
            return '';
        }

        $parts = collect(preg_split('/[\s\-\/]+/u', $trimmed) ?: [])
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->values();

        if ($parts->count() >= 2) {
            return mb_strtoupper(
                mb_substr((string) $parts[0], 0, 1).mb_substr((string) $parts[1], 0, 1)
            );
        }

        $first = (string) $parts->first();

        return mb_strtoupper(mb_substr($first, 0, min(2, mb_strlen($first))));
    }

    public function resolveLeaveLegendIcon(string $leaveTypeCode, string $absenceCode): ?string
    {
        if (trim($leaveTypeCode) !== '') {
            return null;
        }

        return strtoupper(trim($absenceCode)) === 'LEAVE'
            ? 'icons.personal-affair-icon'
            : null;
    }

    public function resolveLeaveLegendFamilyKey(?int $leaveTypeId, string $leaveTypeCode, string $leaveTypeName, string $absenceCode): string
    {
        if ($leaveTypeId !== null) {
            return 'leave-family:id:'.$leaveTypeId;
        }

        $normalizedCode = trim($leaveTypeCode);
        if ($normalizedCode !== '') {
            return 'leave-family:code:'.$normalizedCode;
        }

        $normalizedName = trim($leaveTypeName);
        if ($normalizedName !== '') {
            return 'leave-family:name:'.$normalizedName;
        }

        $normalizedAbsence = trim($absenceCode);

        return 'leave-family:absence:'.($normalizedAbsence !== '' ? $normalizedAbsence : 'unknown');
    }

    public function buildLeaveLegendFamilyLabel(string $leaveTypeName, string $absenceCode = ''): string
    {
        return $leaveTypeName !== ''
            ? $leaveTypeName
            : ($absenceCode !== '' ? $this->resolveAbsenceDisplayLabel($absenceCode) : __('attendance::puantaj.legend.unknown_leave'));
    }

    public function resolveAbsenceDisplayLabel(string $absenceCode): string
    {
        return match (strtoupper(trim($absenceCode))) {
            'LEAVE' => __('attendance::puantaj.absence_codes.leave'),
            default => strtoupper(trim($absenceCode)),
        };
    }

    public function resolveLeaveTone(?int $leaveTypeId, string $leaveTypeName, string $absenceCode): string
    {
        $palette = ['blue', 'purple', 'red', 'sky', 'green', 'secondary'];
        $seed = $leaveTypeId !== null
            ? (string) $leaveTypeId
            : ($absenceCode !== '' ? $absenceCode : $leaveTypeName);

        return $palette[abs(crc32($seed)) % count($palette)];
    }

    public function resolveLeaveToneClasses(string $tone): string
    {
        return match ($tone) {
            'blue' => 'bg-blue-50/80',
            'purple' => 'bg-violet-50/80',
            'red' => 'bg-rose-50/80',
            'sky' => 'bg-sky-50/80',
            'green' => 'bg-emerald-50/80',
            default => 'bg-zinc-50/80',
        };
    }

    public function resolveLeaveToneIconColor(string $tone): string
    {
        return match ($tone) {
            'blue' => 'text-blue-600',
            'purple' => 'text-violet-600',
            'red' => 'text-rose-600',
            'sky' => 'text-sky-600',
            'green' => 'text-emerald-600',
            default => 'text-zinc-600',
        };
    }

    public function resolveLeaveToneBadgeMode(string $tone): string
    {
        return match ($tone) {
            'blue' => 'blue',
            'purple' => 'purple',
            'red' => 'red',
            'sky' => 'sky',
            'green' => 'green',
            default => 'secondary',
        };
    }

    public function resolveLeaveToneCodeClasses(string $tone): string
    {
        return match ($tone) {
            'blue' => 'border-blue-200 bg-blue-100/90 text-blue-700',
            'purple' => 'border-violet-200 bg-violet-100/90 text-violet-700',
            'red' => 'border-rose-200 bg-rose-100/90 text-rose-700',
            'sky' => 'border-sky-200 bg-sky-100/90 text-sky-700',
            'green' => 'border-emerald-200 bg-emerald-100/90 text-emerald-700',
            default => 'border-zinc-200 bg-zinc-100/90 text-zinc-700',
        };
    }
}
