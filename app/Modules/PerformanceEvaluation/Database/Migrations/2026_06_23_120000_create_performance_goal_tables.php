<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_goals')) {
            Schema::create('performance_goals', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_cycle_id');
                $table->unsignedBigInteger('personnel_id')->nullable(); // null = organisation-level goal
                $table->unsignedBigInteger('parent_goal_id')->nullable(); // cascade alignment
                $table->string('goal_type')->default('objective'); // objective | kpi | goal
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('weight_percent', 5, 2)->default(0);
                $table->string('unit')->nullable(); // %, say, AZN, ...
                $table->decimal('target_value', 14, 2)->nullable();
                $table->decimal('current_value', 14, 2)->default(0);
                $table->string('status')->default('active'); // draft | active | at_risk | done | cancelled
                $table->date('due_date')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['performance_cycle_id', 'status'], 'perf_goals_cycle_status_idx');
                $table->index('personnel_id', 'perf_goals_personnel_idx');
                $table->index('parent_goal_id', 'perf_goals_parent_idx');

                $table->foreign('performance_cycle_id', 'perf_goals_cycle_fk')
                    ->references('id')->on('performance_cycles')->cascadeOnDelete();
                $table->foreign('parent_goal_id', 'perf_goals_parent_fk')
                    ->references('id')->on('performance_goals')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('performance_goal_checkins')) {
            Schema::create('performance_goal_checkins', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_goal_id');
                $table->decimal('value', 14, 2);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('performance_goal_id', 'perf_goal_checkins_goal_idx');
                $table->foreign('performance_goal_id', 'perf_goal_checkins_goal_fk')
                    ->references('id')->on('performance_goals')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_goal_checkins');
        Schema::dropIfExists('performance_goals');
    }
};
