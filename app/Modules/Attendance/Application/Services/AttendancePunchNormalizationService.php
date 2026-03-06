<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceRawPunch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendancePunchNormalizationService
{
    /**
     * Normalize unprocessed punches in selected range.
     *
     * @return array{groups:int,total:int,normalized:int}
     */
    public function normalize(Carbon $from, Carbon $to, ?string $source = null, ?array $tabelNos = null): array
    {
        $punches = AttendanceRawPunch::query()
            ->where('is_processed', false)
            ->whereBetween('punched_at', [$from, $to])
            ->when($source, fn ($query) => $query->where('source', $source))
            ->when(
                is_array($tabelNos) && ! empty($tabelNos),
                fn ($query) => $query->whereIn('tabel_no', $tabelNos)
            )
            ->orderBy('tabel_no')
            ->orderBy('punched_at')
            ->get(['id', 'tabel_no', 'punched_at', 'direction']);

        $groups = $punches->groupBy(
            fn (AttendanceRawPunch $punch) => $punch->tabel_no.'|'.$punch->punched_at->toDateString()
        );

        $normalizedCount = 0;

        /** @var Collection<int,AttendanceRawPunch> $group */
        foreach ($groups as $group) {
            $expectsIn = true;

            foreach ($group as $punch) {
                $direction = $this->normalizeDirection($punch->direction);
                if ($direction === null) {
                    $direction = $expectsIn ? 'in' : 'out';
                    AttendanceRawPunch::query()
                        ->whereKey($punch->id)
                        ->update(['direction' => $direction]);
                    $normalizedCount++;
                }

                $expectsIn = in_array($direction, ['out', 'break_in'], true);
            }
        }

        return [
            'groups' => $groups->count(),
            'total' => $punches->count(),
            'normalized' => $normalizedCount,
        ];
    }

    private function normalizeDirection(?string $direction): ?string
    {
        if ($direction === null) {
            return null;
        }

        $value = strtolower(trim($direction));

        return in_array($value, ['in', 'out', 'break_in', 'break_out'], true)
            ? $value
            : null;
    }
}
