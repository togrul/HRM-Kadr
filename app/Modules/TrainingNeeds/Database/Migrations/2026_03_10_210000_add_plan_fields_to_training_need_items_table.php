<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_need_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('training_need_items', 'target_level_id')) {
                $table->foreignId('target_level_id')
                    ->nullable()
                    ->after('recommended_program_id')
                    ->constrained('training_levels')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('training_need_items', 'target_completion_date')) {
                $table->date('target_completion_date')->nullable()->after('status');
            }

            if (! Schema::hasColumn('training_need_items', 'plan_note')) {
                $table->text('plan_note')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('training_need_items', function (Blueprint $table): void {
            if (Schema::hasColumn('training_need_items', 'target_level_id')) {
                $table->dropConstrainedForeignId('target_level_id');
            }

            if (Schema::hasColumn('training_need_items', 'target_completion_date')) {
                $table->dropColumn('target_completion_date');
            }

            if (Schema::hasColumn('training_need_items', 'plan_note')) {
                $table->dropColumn('plan_note');
            }
        });
    }
};
