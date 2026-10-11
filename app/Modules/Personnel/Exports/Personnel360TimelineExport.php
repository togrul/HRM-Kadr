<?php

namespace App\Modules\Personnel\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Əməkdaş 360 zaman xəttinin XLSX ixracı. Sətirlər artıq redaksiya olunmuş gəlir
 * ({@see \App\Modules\Personnel\Application\Services\Personnel360ExportService}); mətn
 * dəyərləri config/excel.php-dəki SafeValueBinder ilə formula yeridilməsindən qorunur.
 */
class Personnel360TimelineExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  list<string>  $headings
     * @param  list<list<string>>  $rows
     */
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title,
    ) {}

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }
}
