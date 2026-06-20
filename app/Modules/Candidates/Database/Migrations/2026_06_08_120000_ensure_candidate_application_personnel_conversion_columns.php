<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_applications')) {
            return;
        }

        Schema::table('candidate_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('candidate_applications', 'personnel_id')) {
                $table->foreignId('personnel_id')
                    ->nullable()
                    ->after('hired_at')
                    ->constrained('personnels')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('candidate_applications', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('personnel_id');
            }

            if (! Schema::hasColumn('candidate_applications', 'converted_by')) {
                $table->foreignId('converted_by')
                    ->nullable()
                    ->after('converted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (! $this->hasIndex('candidate_applications', 'candidate_applications_personnel_conversion_idx')) {
            Schema::table('candidate_applications', function (Blueprint $table): void {
                $table->index(['personnel_id', 'converted_at'], 'candidate_applications_personnel_conversion_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('candidate_applications')) {
            return;
        }

        Schema::table('candidate_applications', function (Blueprint $table): void {
            if ($this->hasIndex('candidate_applications', 'candidate_applications_personnel_conversion_idx')) {
                $table->dropIndex('candidate_applications_personnel_conversion_idx');
            }
        });
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
