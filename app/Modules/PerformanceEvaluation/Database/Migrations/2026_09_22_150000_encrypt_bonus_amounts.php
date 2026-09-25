<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bonus and salary figures are stored encrypted (spec §13 privacy): the columns turn
 * into text and every existing value is re-written through the app key.
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private const COLUMNS = [
        'performance_bonus_calculations' => ['base_salary', 'amount'],
        'performance_bonus_rules' => ['fund'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $rows = DB::table($table)->get(['id', ...$columns]);

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->text($column)->nullable()->change();
                }
            });

            foreach ($rows as $row) {
                DB::table($table)->where('id', $row->id)->update(collect($columns)->mapWithKeys(fn (string $column): array => [
                    $column => $row->{$column} === null ? null : Crypt::encryptString((string) (float) $row->{$column}),
                ])->all());
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $rows = DB::table($table)->get(['id', ...$columns]);
            foreach ($rows as $row) {
                DB::table($table)->where('id', $row->id)->update(collect($columns)->mapWithKeys(fn (string $column): array => [
                    $column => $row->{$column} === null ? null : Crypt::decryptString($row->{$column}),
                ])->all());
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->decimal($column, 14, 2)->nullable()->change();
                }
            });
        }
    }
};
