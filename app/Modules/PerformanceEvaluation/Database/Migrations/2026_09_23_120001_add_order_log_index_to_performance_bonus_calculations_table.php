<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order-mode bonus lines are looked up by their award order.
 */
return new class extends Migration
{
    /** @var list<list<string>> */
    private array $indexes = [['order_log_id']];

    public function up(): void
    {
        if (! Schema::hasTable('performance_bonus_calculations')) {
            return;
        }

        $existing = collect(Schema::getIndexes('performance_bonus_calculations'))->pluck('columns')->map(fn (array $columns): string => implode(',', $columns))->all();

        Schema::table('performance_bonus_calculations', function (Blueprint $table) use ($existing): void {
            foreach ($this->indexes as $columns) {
                if (! in_array(implode(',', $columns), $existing, true)) {
                    $table->index($columns);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('performance_bonus_calculations')) {
            return;
        }

        Schema::table('performance_bonus_calculations', function (Blueprint $table): void {
            foreach ($this->indexes as $columns) {
                $table->dropIndex($columns);
            }
        });
    }
};
