<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The home approval queue filters business trips by approval_status.
 */
return new class extends Migration
{
    /** @var list<list<string>> */
    private array $indexes = [['approval_status']];

    public function up(): void
    {
        if (! Schema::hasTable('personnel_business_trips')) {
            return;
        }

        $existing = collect(Schema::getIndexes('personnel_business_trips'))->pluck('columns')->map(fn (array $columns): string => implode(',', $columns))->all();

        Schema::table('personnel_business_trips', function (Blueprint $table) use ($existing): void {
            foreach ($this->indexes as $columns) {
                if (! in_array(implode(',', $columns), $existing, true)) {
                    $table->index($columns);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('personnel_business_trips')) {
            return;
        }

        Schema::table('personnel_business_trips', function (Blueprint $table): void {
            foreach ($this->indexes as $columns) {
                $table->dropIndex($columns);
            }
        });
    }
};
