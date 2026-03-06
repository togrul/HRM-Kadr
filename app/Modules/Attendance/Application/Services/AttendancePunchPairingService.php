<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceRawPunch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendancePunchPairingService
{
    /**
     * @param  Collection<int,AttendanceRawPunch>  $punches
     * @return array{
     *   worked_minutes:int,
     *   break_minutes:int,
     *   unmatched:int,
     *   missing_in:bool,
     *   missing_out:bool,
     *   first_in_at:?string,
     *   last_out_at:?string,
     *   consumed_punch_ids:array<int,int>,
     *   pairs:array<int,array{in:string,out:string,duration_minutes:int}>
     * }
     */
    public function pair(Collection $punches): array
    {
        $sorted = $punches
            ->sortBy(fn (AttendanceRawPunch $punch) => $punch->punched_at?->timestamp ?? 0)
            ->values();

        $openIn = null;
        $openBreak = null;
        $firstInAt = null;
        $lastOutAt = null;
        $grossWorked = 0;
        $breakMinutes = 0;
        $unmatched = 0;
        $missingIn = false;
        $missingOut = false;
        $pairs = [];
        $consumedIds = [];

        /** @var AttendanceRawPunch $punch */
        foreach ($sorted as $punch) {
            $time = $punch->punched_at instanceof Carbon
                ? $punch->punched_at->copy()
                : Carbon::parse((string) $punch->punched_at);
            $direction = $this->normalizeDirection($punch->direction);
            if ($punch->id) {
                $consumedIds[] = (int) $punch->id;
            }

            if ($direction === 'in') {
                if ($openIn !== null) {
                    $unmatched++;
                    $missingOut = true;
                }
                $openIn = $time;
                $openBreak = null;
                $firstInAt ??= $time;
                continue;
            }

            if ($direction === 'break_out') {
                if ($openIn !== null && $openBreak === null) {
                    $openBreak = $time;
                } else {
                    $unmatched++;
                    $missingIn = true;
                }
                continue;
            }

            if ($direction === 'break_in') {
                if ($openIn !== null && $openBreak !== null) {
                    $breakMinutes += max(0, $openBreak->diffInMinutes($time));
                    $openBreak = null;
                } else {
                    $unmatched++;
                    $missingOut = true;
                }
                continue;
            }

            // out
            if ($openIn === null) {
                $unmatched++;
                $missingIn = true;
                continue;
            }

            if ($openBreak !== null) {
                $breakMinutes += max(0, $openBreak->diffInMinutes($time));
                $openBreak = null;
            }

            $duration = max(0, $openIn->diffInMinutes($time));
            $grossWorked += $duration;
            $lastOutAt = $time;
            $pairs[] = [
                'in' => $openIn->toDateTimeString(),
                'out' => $time->toDateTimeString(),
                'duration_minutes' => $duration,
            ];
            $openIn = null;
        }

        if ($openIn !== null) {
            $unmatched++;
            $missingOut = true;
        }

        if ($openBreak !== null) {
            $unmatched++;
            $missingOut = true;
        }

        $workedMinutes = max(0, $grossWorked - $breakMinutes);

        return [
            'worked_minutes' => (int) $workedMinutes,
            'break_minutes' => (int) $breakMinutes,
            'unmatched' => (int) $unmatched,
            'missing_in' => $missingIn,
            'missing_out' => $missingOut,
            'first_in_at' => $firstInAt?->toDateTimeString(),
            'last_out_at' => $lastOutAt?->toDateTimeString(),
            'consumed_punch_ids' => array_values(array_unique($consumedIds)),
            'pairs' => $pairs,
        ];
    }

    private function normalizeDirection(?string $direction): string
    {
        $value = strtolower(trim((string) $direction));

        if (in_array($value, ['in', 'out', 'break_in', 'break_out'], true)) {
            return $value;
        }

        return 'out';
    }
}
