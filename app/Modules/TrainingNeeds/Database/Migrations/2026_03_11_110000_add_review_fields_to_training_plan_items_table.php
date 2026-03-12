<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_plan_items')) {
            return;
        }

        Schema::table('training_plan_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('training_plan_items', 'review_status')) {
                $table->string('review_status')->default('suggested')->after('source_mix');
            }

            if (! Schema::hasColumn('training_plan_items', 'suggested_score')) {
                $table->decimal('suggested_score', 8, 1)->nullable()->after('review_status');
            }

            if (! Schema::hasColumn('training_plan_items', 'review_note')) {
                $table->text('review_note')->nullable()->after('suggested_score');
            }

            if (! Schema::hasColumn('training_plan_items', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_note');
            }

            if (! Schema::hasColumn('training_plan_items', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        Schema::table('training_plan_items', function (Blueprint $table): void {
            if (Schema::hasColumn('training_plan_items', 'reviewed_by')) {
                $table->foreign('reviewed_by', 'training_plan_items_reviewed_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('training_plan_items')) {
            return;
        }

        Schema::table('training_plan_items', function (Blueprint $table): void {
            if (Schema::hasColumn('training_plan_items', 'reviewed_by')) {
                try {
                    $table->dropForeign('training_plan_items_reviewed_by_fk');
                } catch (Throwable) {
                    // ignore rollback guard
                }
            }
        });

        Schema::table('training_plan_items', function (Blueprint $table): void {
            foreach (['reviewed_at', 'reviewed_by', 'review_note', 'suggested_score', 'review_status'] as $column) {
                if (Schema::hasColumn('training_plan_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
