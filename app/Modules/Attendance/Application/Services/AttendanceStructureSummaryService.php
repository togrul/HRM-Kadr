<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceDailyStructureSummary;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceStructureSummaryService
{
    /**
     * @param  array<int,int>|null  $structureIds
     * @return array{deleted:int,upserts:int}
     */
    public function rebuildRange(Carbon $from, Carbon $to, ?array $structureIds = null): array
    {
        $fromDate = $from->copy()->startOfDay()->toDateString();
        $toDate = $to->copy()->endOfDay()->toDateString();
        $scopes = $this->normalizeScope($structureIds);

        $rows = DB::table('attendance_daily_ledgers as l')
            ->join('personnels as p', 'p.tabel_no', '=', 'l.tabel_no')
            ->whereBetween('l.date', [$fromDate, $toDate])
            ->when(
                $scopes !== null,
                fn ($query) => $query->whereIn('p.structure_id', $scopes)
            )
            ->selectRaw('l.date as date, p.structure_id as structure_id')
            ->selectRaw('COUNT(*) as ledger_rows')
            ->selectRaw('COALESCE(SUM(CASE WHEN l.scheduled_minutes > 0 THEN 1 ELSE 0 END),0) as scheduled_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN l.worked_minutes > 0 OR l.attendance_status IN ("present","manual_present","holiday_worked","weekend_worked") THEN 1 ELSE 0 END),0) as present_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN l.attendance_status IN ("absent","manual_absence") THEN 1 ELSE 0 END),0) as absence_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN l.scheduled_minutes > 0 AND l.attendance_status NOT IN ("absent","manual_absence") AND l.late_minutes = 0 AND l.early_leave_minutes = 0 THEN 1 ELSE 0 END),0) as compliant_days')
            ->selectRaw('COALESCE(SUM(l.scheduled_minutes),0) as scheduled_minutes_sum')
            ->selectRaw('COALESCE(SUM(l.worked_minutes),0) as worked_minutes_sum')
            ->selectRaw('COALESCE(SUM(l.overtime_minutes),0) as overtime_minutes_sum')
            ->selectRaw('COALESCE(SUM(l.late_minutes),0) as late_minutes_sum')
            ->selectRaw('COALESCE(SUM(l.early_leave_minutes),0) as early_leave_minutes_sum')
            ->groupBy('l.date', 'p.structure_id')
            ->get();

        $deleted = AttendanceDailyStructureSummary::query()
            ->whereBetween('date', [$fromDate, $toDate])
            ->when(
                $scopes !== null,
                fn ($query) => $query->whereIn('structure_id', $scopes)
            )
            ->delete();

        $payload = $this->prepareUpsertPayload($rows);
        if ($payload !== []) {
            AttendanceDailyStructureSummary::query()->upsert(
                $payload,
                ['date', 'structure_id'],
                [
                    'ledger_rows',
                    'scheduled_days',
                    'present_days',
                    'absence_days',
                    'compliant_days',
                    'scheduled_minutes_sum',
                    'worked_minutes_sum',
                    'overtime_minutes_sum',
                    'late_minutes_sum',
                    'early_leave_minutes_sum',
                    'updated_at',
                ]
            );
        }

        return [
            'deleted' => (int) $deleted,
            'upserts' => count($payload),
        ];
    }

    /**
     * @param  Collection<int,object>  $rows
     * @return array<int,array<string,mixed>>
     */
    private function prepareUpsertPayload(Collection $rows): array
    {
        $now = now();

        return $rows->map(function (object $row) use ($now): array {
            return [
                'date' => (string) $row->date,
                'structure_id' => $row->structure_id !== null ? (int) $row->structure_id : null,
                'ledger_rows' => (int) $row->ledger_rows,
                'scheduled_days' => (int) $row->scheduled_days,
                'present_days' => (int) $row->present_days,
                'absence_days' => (int) $row->absence_days,
                'compliant_days' => (int) $row->compliant_days,
                'scheduled_minutes_sum' => (int) $row->scheduled_minutes_sum,
                'worked_minutes_sum' => (int) $row->worked_minutes_sum,
                'overtime_minutes_sum' => (int) $row->overtime_minutes_sum,
                'late_minutes_sum' => (int) $row->late_minutes_sum,
                'early_leave_minutes_sum' => (int) $row->early_leave_minutes_sum,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();
    }

    /**
     * @param  array<int,int>|null  $structureIds
     * @return array<int,int>|null
     */
    private function normalizeScope(?array $structureIds): ?array
    {
        if (! is_array($structureIds)) {
            return null;
        }

        $normalized = collect($structureIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }
}
