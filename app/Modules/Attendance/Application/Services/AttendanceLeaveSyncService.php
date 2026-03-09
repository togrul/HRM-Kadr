<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceLeaveSyncService
{
    /**
     * @param  array<string,mixed>  $original
     */
    public function syncLeaveChange(Leave $leave, array $original = []): void
    {
        $ranges = collect()
            ->merge($this->resolveRangesFromCurrent($leave))
            ->merge($this->resolveRangesFromOriginal($original))
            ->filter(fn ($range) => is_array($range) && ! empty($range['tabel_no']) && ! empty($range['from']) && ! empty($range['to']))
            ->unique(fn (array $range) => implode('|', [$range['tabel_no'], $range['from'], $range['to']]))
            ->values();

        if ($ranges->isEmpty()) {
            return;
        }

        DB::afterCommit(function () use ($ranges): void {
            $pipeline = app(AttendancePunchProcessingPipelineService::class);

            foreach ($ranges as $range) {
                $pipeline->process(
                    from: Carbon::parse((string) $range['from'])->startOfDay(),
                    to: Carbon::parse((string) $range['to'])->endOfDay(),
                    source: null,
                    options: [
                        'include_processed' => true,
                        'mark_processed' => false,
                        'structure_id' => null,
                        'tabel_nos' => [(string) $range['tabel_no']],
                    ]
                );
            }
        });
    }

    /**
     * @return array<int,array{tabel_no:string,from:string,to:string}>
     */
    private function resolveRangesFromCurrent(Leave $leave): array
    {
        $statusId = $this->resolveCurrentStatusId($leave);
        $approvedAt = $this->resolveCurrentApprovedAt($leave);

        if ($statusId === null || ! $this->isAttendanceRelevant($statusId, $approvedAt !== null)) {
            return [];
        }

        $tabelNo = $this->resolveCurrentTabelNo($leave);
        $from = $this->resolveCurrentDateString($leave, 'starts_at');
        $to = $this->resolveCurrentDateString($leave, 'ends_at');

        if ($tabelNo === '' || $from === null || $to === null) {
            return [];
        }

        return [[
            'tabel_no' => $tabelNo,
            'from' => $from,
            'to' => $to,
        ]];
    }

    /**
     * @param  array<string,mixed>  $original
     * @return array<int,array{tabel_no:string,from:string,to:string}>
     */
    private function resolveRangesFromOriginal(array $original): array
    {
        $statusId = is_numeric($original['status_id'] ?? null) ? (int) $original['status_id'] : null;
        $approvedAt = $original['approved_at'] ?? null;

        if ($statusId === null || ! $this->isAttendanceRelevant($statusId, filled($approvedAt))) {
            return [];
        }

        $tabelNo = trim((string) ($original['tabel_no'] ?? ''));
        $from = $original['starts_at'] ?? null;
        $to = $original['ends_at'] ?? null;

        if ($tabelNo === '' || ! filled($from) || ! filled($to)) {
            return [];
        }

        return [[
            'tabel_no' => $tabelNo,
            'from' => Carbon::parse((string) $from)->toDateString(),
            'to' => Carbon::parse((string) $to)->toDateString(),
        ]];
    }

    private function isAttendanceRelevant(?int $statusId, bool $isApprovedByTimestamp): bool
    {
        if ($statusId === \App\Enums\OrderStatusEnum::APPROVED->value) {
            return true;
        }

        return $statusId === null && $isApprovedByTimestamp;
    }

    private function resolveCurrentStatusId(Leave $leave): ?int
    {
        $value = $leave->getAttributes()['status_id'] ?? $leave->getOriginal('status_id');

        return is_numeric($value) ? (int) $value : null;
    }

    private function resolveCurrentApprovedAt(Leave $leave): ?string
    {
        $value = $leave->getAttributes()['approved_at'] ?? $leave->getOriginal('approved_at');

        return filled($value) ? (string) $value : null;
    }

    private function resolveCurrentTabelNo(Leave $leave): string
    {
        $value = $leave->getAttributes()['tabel_no'] ?? $leave->getOriginal('tabel_no');

        return trim((string) $value);
    }

    private function resolveCurrentDateString(Leave $leave, string $key): ?string
    {
        $value = $leave->getAttributes()[$key] ?? $leave->getOriginal($key);

        if (! filled($value)) {
            return null;
        }

        return Carbon::parse((string) $value)->toDateString();
    }
}
