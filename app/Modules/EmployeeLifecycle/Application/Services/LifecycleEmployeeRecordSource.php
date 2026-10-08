<?php

namespace App\Modules\EmployeeLifecycle\Application\Services;

use App\Contracts\EmployeeRecordSource;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;

/**
 * Həyat dövrünün töhfəsi: kadr hərəkəti tarixçəsi (keçirilmə, vəzifə dəyişikliyi və s.) və
 * işə qəbulun özünün açmadığı hadisələr. Namizədin işə qəbulu ilə açılan adaptasiya hadisəsi
 * (`candidate_*` mənbəli) qəbulun bir hissəsidir və sayılmır.
 */
class LifecycleEmployeeRecordSource implements EmployeeRecordSource
{
    private const HIRE_SOURCES = ['candidate_order_conversion', 'candidate_application'];

    public function recordCounts(int $personnelId, string $tabelNo): array
    {
        if (! InstalledTables::has('employee_lifecycle_events')) {
            return [];
        }

        $counts = [];

        $counts['lifecycle_events'] = DB::table('employee_lifecycle_events')
            ->where('personnel_id', $personnelId)
            ->where(fn ($query) => $query->whereNull('source_type')->orWhereNotIn('source_type', self::HIRE_SOURCES))
            ->count();

        if (InstalledTables::has('employee_lifecycle_movements')) {
            $counts['staff_movements'] = DB::table('employee_lifecycle_movements')
                ->join('employee_lifecycle_events', 'employee_lifecycle_events.id', '=', 'employee_lifecycle_movements.event_id')
                ->where('employee_lifecycle_movements.personnel_id', $personnelId)
                ->where(fn ($query) => $query
                    ->whereNull('employee_lifecycle_events.source_type')
                    ->orWhereNotIn('employee_lifecycle_events.source_type', self::HIRE_SOURCES))
                ->count();
        }

        return array_filter($counts);
    }
}
