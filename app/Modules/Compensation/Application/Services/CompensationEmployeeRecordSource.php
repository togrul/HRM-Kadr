<?php

namespace App\Modules\Compensation\Application\Services;

use App\Contracts\EmployeeRecordSource;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;

/**
 * Kompensasiyanın töhfəsi: işə qəbulun avtomatik yaratdığı layihədən başqa hər əmək haqqı qeydi
 * və bank hesabları. Avtomatik layihəni geri alma prosesi özü silir.
 */
class CompensationEmployeeRecordSource implements EmployeeRecordSource
{
    public function recordCounts(int $personnelId, string $tabelNo): array
    {
        $counts = [];

        if (InstalledTables::has('employee_compensations')) {
            $counts['compensations'] = DB::table('employee_compensations')
                ->where('tabel_no', $tabelNo)
                ->where(function ($query): void {
                    $query->where('status', '!=', 'draft')
                        ->orWhereNull('note')
                        ->orWhere('note', '!=', CompensationService::HIRE_DRAFT_NOTE);
                })
                ->count();
        }

        if (InstalledTables::has('employee_bank_accounts')) {
            $counts['bank_accounts'] = DB::table('employee_bank_accounts')->where('tabel_no', $tabelNo)->count();
        }

        return array_filter($counts);
    }
}
