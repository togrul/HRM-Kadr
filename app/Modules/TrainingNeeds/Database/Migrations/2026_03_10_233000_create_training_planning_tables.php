<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_annual_plans')) {
            Schema::create('training_annual_plans', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->unsignedSmallInteger('plan_year');
                $table->unsignedTinyInteger('plan_quarter')->nullable();
                $table->string('status')->default('draft');
                $table->decimal('estimated_budget', 12, 2)->nullable();
                $table->unsignedInteger('planned_participants')->default(0);
                $table->unsignedInteger('covered_need_count')->default(0);
                $table->boolean('auto_generated')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['plan_year', 'plan_quarter', 'status'], 'training_annual_plans_scope_idx');
            });
        }

        if (! Schema::hasTable('training_plan_items')) {
            Schema::create('training_plan_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('training_annual_plan_id');
                $table->unsignedBigInteger('training_competency_id')->nullable();
                $table->unsignedBigInteger('training_program_id')->nullable();
                $table->integer('position_id')->nullable();
                $table->unsignedBigInteger('target_level_id')->nullable();
                $table->string('priority')->default('medium');
                $table->unsignedInteger('participant_count')->default(0);
                $table->unsignedInteger('need_count')->default(0);
                $table->decimal('estimated_budget', 12, 2)->nullable();
                $table->string('source_mix')->nullable();
                $table->timestamps();
                $table->foreign('training_annual_plan_id', 'training_plan_items_plan_fk')
                    ->references('id')
                    ->on('training_annual_plans')
                    ->cascadeOnDelete();
                $table->foreign('training_competency_id', 'training_plan_items_competency_fk')
                    ->references('id')
                    ->on('training_competencies')
                    ->nullOnDelete();
                $table->foreign('training_program_id', 'training_plan_items_program_fk')
                    ->references('id')
                    ->on('training_programs')
                    ->nullOnDelete();
                $table->foreign('position_id', 'training_plan_items_position_fk')
                    ->references('id')
                    ->on('positions')
                    ->nullOnDelete();
                $table->foreign('target_level_id', 'training_plan_items_target_level_fk')
                    ->references('id')
                    ->on('training_levels')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_plan_items');
        Schema::dropIfExists('training_annual_plans');
    }
};
