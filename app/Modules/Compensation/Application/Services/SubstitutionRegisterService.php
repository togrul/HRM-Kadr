<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\EmployeeSubstitution;
use App\Modules\Compensation\Contracts\SubstitutionRegister;
use App\Support\Database\InstalledTables;

class SubstitutionRegisterService implements SubstitutionRegister
{
    public function overlapping(array $tabelNos, string $from, string $to): array
    {
        if ($tabelNos === [] || ! InstalledTables::has('employee_substitutions')) {
            return [];
        }

        $rows = EmployeeSubstitution::query()
            ->whereIn('tabel_no', $tabelNos)
            ->whereDate('start_date', '<=', $to)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $from))
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row->tabel_no][] = [
                'id' => (int) $row->id,
                'substituted_tabel_no' => $row->substituted_tabel_no,
                'substituted_name' => $row->substituted_name,
                'substituted_position_id' => $row->substituted_position_id !== null ? (int) $row->substituted_position_id : null,
                'start_date' => $row->start_date->toDateString(),
                'end_date' => $row->end_date?->toDateString(),
                'extra_pay_percent' => $row->extra_pay_percent !== null ? (float) $row->extra_pay_percent : null,
                'extra_pay_amount' => $row->extra_pay_amount !== null ? (float) $row->extra_pay_amount : null,
                'order_no' => $row->order_no,
            ];
        }

        return $result;
    }
}
