<?php

namespace App\Modules\Payroll\Application\Services;

use App\Contracts\EmployeeRecordSource;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;

/**
 * Əmək haqqının töhfəsi: hesablanmış payslip-lər, kreditlər, birdəfəlik ödənişlər, retro ödənişlər.
 */
class PayrollEmployeeRecordSource implements EmployeeRecordSource
{
    private const TABLES = [
        'payslips' => 'payslips',
        'employee_loans' => 'employee_loans',
        'payroll_one_off_earnings' => 'payroll_one_off_earnings',
        'retro_payments' => 'retro_payments',
    ];

    public function recordCounts(int $personnelId, string $tabelNo): array
    {
        $counts = [];

        foreach (self::TABLES as $key => $table) {
            if (InstalledTables::has($table)) {
                $counts[$key] = DB::table($table)->where('tabel_no', $tabelNo)->count();
            }
        }

        return array_filter($counts);
    }
}
