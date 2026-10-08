<?php

namespace App\Modules\Orders\Infrastructure\Persistence;

use App\Contracts\EmployeeRecordSource;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;

/**
 * Orders-in töhfəsi: əməkdaşa bağlanmış (silinməmiş) əmrlər və əməkdaşın imzaladığı əmrlər.
 * İşə qəbul əmri əməkdaş yaranmamışdan əvvəl buraxıldığı üçün ona bağlanmır və sayılmır.
 */
class OrderEmployeeRecordSource implements EmployeeRecordSource
{
    public function recordCounts(int $personnelId, string $tabelNo): array
    {
        if (! InstalledTables::has('order_logs')) {
            return [];
        }

        $counts = [];

        if (InstalledTables::has('order_log_personnels')) {
            $counts['orders'] = DB::table('order_log_personnels')
                ->join('order_logs', 'order_logs.order_no', '=', 'order_log_personnels.order_no')
                ->where('order_log_personnels.tabel_no', $tabelNo)
                ->whereNull('order_logs.deleted_at')
                ->distinct()
                ->count('order_logs.id');
        }

        if (InstalledTables::hasColumn('order_logs', 'signatory_personnel_id')) {
            $counts['signed_orders'] = DB::table('order_logs')
                ->where('signatory_personnel_id', $personnelId)
                ->whereNull('deleted_at')
                ->count();
        }

        return array_filter($counts);
    }
}
