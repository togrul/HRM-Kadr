<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-position lookups and the stage-deadline reminder sweep (stage_due_at).
 */
return new class extends Migration
{
    /** @var list<list<string>> */
    private array $indexes = [['position_id'], ['stage_due_at']];

    public function up(): void
    {
        if (! Schema::hasTable('performance_scorecards')) {
            return;
        }

        $existing = collect(Schema::getIndexes('performance_scorecards'))->pluck('columns')->map(fn (array $columns): string => implode(',', $columns))->all();

        Schema::table('performance_scorecards', function (Blueprint $table) use ($existing): void {
            foreach ($this->indexes as $columns) {
                if (! in_array(implode(',', $columns), $existing, true)) {
                    $table->index($columns);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('performance_scorecards')) {
            return;
        }

        Schema::table('performance_scorecards', function (Blueprint $table): void {
            foreach ($this->indexes as $columns) {
                $table->dropIndex($columns);
            }
        });
    }
};
