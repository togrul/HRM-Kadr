<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceMonthlySummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendancePayrollExportService
{
    /**
     * @param  array<int,int>  $structureIds  AttendanceStructureScopeReadService::resolveIds() nəticəsi;
     *                                        boş massiv yalnız «bütün strukturlar» istifadəçisi üçündür.
     * @return Collection<int,AttendanceMonthlySummary>
     */
    public function rows(int $year, int $month, array $structureIds = []): Collection
    {
        return AttendanceMonthlySummary::query()
            ->with(['personnel:tabel_no,surname,name,patronymic'])
            ->where('year', $year)
            ->where('month', $month)
            ->when($structureIds !== [], fn ($query) => $query->whereIn(
                'tabel_no',
                DB::table('personnels')->select('tabel_no')->whereIn('structure_id', $structureIds)
            ))
            ->orderBy('tabel_no')
            ->get();
    }
}
