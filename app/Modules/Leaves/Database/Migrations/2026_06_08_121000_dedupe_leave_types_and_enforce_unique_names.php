<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'leave_types_name_unique';

    public function up(): void
    {
        if (! Schema::hasTable('leave_types')) {
            return;
        }

        $groups = DB::table('leave_types')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->groupBy(fn ($row) => mb_strtolower(trim((string) $row->name)));

        foreach ($groups as $rows) {
            if ($rows->count() < 2) {
                continue;
            }

            $keeper = $rows->first();
            $duplicateIds = $rows->skip(1)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

            if (Schema::hasTable('leaves')) {
                DB::table('leaves')
                    ->whereIn('leave_type_id', $duplicateIds)
                    ->update(['leave_type_id' => (int) $keeper->id]);
            }

            DB::table('leave_types')->whereIn('id', $duplicateIds)->delete();
        }

        if (! $this->hasIndex('leave_types', self::UNIQUE_INDEX)) {
            Schema::table('leave_types', function (Blueprint $table): void {
                $table->unique('name', self::UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('leave_types')) {
            return;
        }

        if ($this->hasIndex('leave_types', self::UNIQUE_INDEX)) {
            Schema::table('leave_types', function (Blueprint $table): void {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
