<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The leaves list sorts by created_at; date filters range over starts_at/ends_at.
 */
return new class extends Migration
{
    /** @var list<list<string>> */
    private array $indexes = [['created_at'], ['starts_at', 'ends_at']];

    public function up(): void
    {
        if (! Schema::hasTable('leaves')) {
            return;
        }

        $existing = collect(Schema::getIndexes('leaves'))->pluck('columns')->map(fn (array $columns): string => implode(',', $columns))->all();

        Schema::table('leaves', function (Blueprint $table) use ($existing): void {
            foreach ($this->indexes as $columns) {
                if (! in_array(implode(',', $columns), $existing, true)) {
                    $table->index($columns);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leaves')) {
            return;
        }

        Schema::table('leaves', function (Blueprint $table): void {
            foreach ($this->indexes as $columns) {
                $table->dropIndex($columns);
            }
        });
    }
};
