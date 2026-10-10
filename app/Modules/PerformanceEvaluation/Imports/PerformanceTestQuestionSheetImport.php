<?php

namespace App\Modules\PerformanceEvaluation\Imports;

use App\Support\Exports\RawImportValueBinder;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * İdxalda dəyərlər olduğu kimi oxunur (ixrac üçün olan formula qoruması tətbiq olunmur).
 */
class PerformanceTestQuestionSheetImport extends RawImportValueBinder implements ToArray, WithHeadingRow
{
    public function array(array $array): array
    {
        return $array;
    }
}
