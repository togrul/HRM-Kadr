<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceException;
use Carbon\Carbon;

class AttendanceExceptionService
{
    /**
     * @param  array{
     *   unmatched?:int,
     *   missing_in?:bool,
     *   missing_out?:bool
     * }  $pairing
     */
    public function syncDayExceptions(string $tabelNo, Carbon $date, array $pairing): void
    {
        $dateString = $date->toDateString();

        $rules = [
            'unmatched_punch' => [
                'active' => ((int) ($pairing['unmatched'] ?? 0)) > 0,
                'message' => sprintf('Unmatched punch count: %d', (int) ($pairing['unmatched'] ?? 0)),
            ],
            'missing_in' => [
                'active' => (bool) ($pairing['missing_in'] ?? false),
                'message' => 'Missing IN punch detected for this day.',
            ],
            'missing_out' => [
                'active' => (bool) ($pairing['missing_out'] ?? false),
                'message' => 'Missing OUT punch detected for this day.',
            ],
        ];

        foreach ($rules as $type => $rule) {
            if ($rule['active']) {
                AttendanceException::query()->updateOrCreate(
                    [
                        'tabel_no' => $tabelNo,
                        'date' => $dateString,
                        'type' => $type,
                    ],
                    [
                        'status' => 'open',
                        'message' => $rule['message'],
                        'resolution_note' => null,
                        'resolved_by' => null,
                        'resolved_at' => null,
                    ]
                );

                continue;
            }

            AttendanceException::query()
                ->where('tabel_no', $tabelNo)
                ->whereDate('date', $dateString)
                ->where('type', $type)
                ->where('status', 'open')
                ->update([
                    'status' => 'resolved',
                    'resolution_note' => 'Auto-resolved by attendance processing pipeline.',
                    'resolved_at' => now(),
                ]);
        }
    }
}

