<?php

namespace App\Modules\Attendance\Exports;

use App\Models\AttendanceMonthlySummary;
use App\Modules\Attendance\Application\Contracts\AttendancePayrollExportContract;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendancePayrollCsvExport implements FromCollection, WithHeadings, WithMapping, WithCustomCsvSettings
{
    private int $rowNo = 0;

    public function __construct(
        public Collection $rows,
        public AttendancePayrollExportContract $contract,
        public array $csvSettings = []
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows->values();
    }

    public function headings(): array
    {
        return $this->contract->headers();
    }

    /**
     * @param  AttendanceMonthlySummary  $row
     * @return array<int,int|float|string>
     */
    public function map($row): array
    {
        $this->rowNo++;

        return $this->contract->mapRow($row, $this->rowNo);
    }

    public function getCsvSettings(): array
    {
        $lineEnding = (string) ($this->csvSettings['line_ending'] ?? PHP_EOL);
        $lineEnding = str_replace(['\\r\\n', '\\n', '\\r'], ["\r\n", "\n", "\r"], $lineEnding);

        return [
            'delimiter' => $this->csvSettings['delimiter'] ?? ';',
            'enclosure' => $this->csvSettings['enclosure'] ?? '"',
            'line_ending' => $lineEnding,
            'use_bom' => (bool) ($this->csvSettings['use_bom'] ?? true),
            'output_encoding' => $this->csvSettings['output_encoding'] ?? 'UTF-8',
        ];
    }
}
