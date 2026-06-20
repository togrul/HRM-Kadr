<?php

namespace App\Modules\Reports\Application\Services;

use Illuminate\Support\Facades\DB;

class ReportsSqlDialectService
{
    public function yearExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(strftime('%Y', {$column}) as integer)";
        }

        return "YEAR({$column})";
    }

    public function monthExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(strftime('%m', {$column}) as integer)";
        }

        return "MONTH({$column})";
    }

    public function quarterExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "((CAST(strftime('%m', {$column}) as integer) - 1) / 3) + 1";
        }

        return "QUARTER({$column})";
    }

    public function ageYearsExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST((julianday('now') - julianday({$column})) / 365.25 as integer)";
        }

        return "TIMESTAMPDIFF(YEAR, {$column}, CURDATE())";
    }

    public function tenureYearsExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST((julianday('now') - julianday({$column})) / 365.25 as integer)";
        }

        return "TIMESTAMPDIFF(YEAR, {$column}, CURDATE())";
    }
}
