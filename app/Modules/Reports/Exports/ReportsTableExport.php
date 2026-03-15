<?php

namespace App\Modules\Reports\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportsTableExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int,array<string,mixed>>  $rows
     * @param  array<int,array{key:string,label:string}>  $columns
     */
    public function __construct(
        protected Collection $rows,
        protected array $columns
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return array_map(fn (array $column) => $column['label'], $this->columns);
    }

    /**
     * @param  array<string,mixed>  $row
     * @return array<int,mixed>
     */
    public function map($row): array
    {
        return array_map(
            fn (array $column) => data_get($row, $column['key']),
            $this->columns
        );
    }
}
