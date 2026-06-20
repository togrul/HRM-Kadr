<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_sessions')) {
            return;
        }

        Schema::table('training_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('training_sessions', 'actual_budget')) {
                $table->decimal('actual_budget', 12, 2)->nullable()->after('planned_budget');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('training_sessions') || ! Schema::hasColumn('training_sessions', 'actual_budget')) {
            return;
        }

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropColumn('actual_budget');
        });
    }
};
