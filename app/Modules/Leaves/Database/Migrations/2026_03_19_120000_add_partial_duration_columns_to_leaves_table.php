<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (! Schema::hasColumn('leaves', 'duration_unit')) {
                $table->string('duration_unit', 16)->default('day')->after('ends_at');
            }

            if (! Schema::hasColumn('leaves', 'partial_day_part')) {
                $table->string('partial_day_part', 16)->nullable()->after('duration_unit');
            }

            if (! Schema::hasColumn('leaves', 'starts_time')) {
                $table->time('starts_time')->nullable()->after('partial_day_part');
            }

            if (! Schema::hasColumn('leaves', 'ends_time')) {
                $table->time('ends_time')->nullable()->after('starts_time');
            }

            if (! Schema::hasColumn('leaves', 'total_minutes')) {
                $table->unsignedInteger('total_minutes')->nullable()->after('total_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            foreach (['total_minutes', 'ends_time', 'starts_time', 'partial_day_part', 'duration_unit'] as $column) {
                if (Schema::hasColumn('leaves', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
