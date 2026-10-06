<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 9-box: each person assessed on performance (1-3) × potential (1-3).
        if (! Schema::hasTable('talent_assessments')) {
            Schema::create('talent_assessments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('personnel_id');
                $table->unsignedBigInteger('performance_cycle_id')->nullable();
                $table->unsignedTinyInteger('performance_level')->default(2); // 1 low · 2 medium · 3 high
                $table->unsignedTinyInteger('potential_level')->default(2);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('assessed_by')->nullable();
                $table->timestamps();

                $table->unique(['personnel_id', 'performance_cycle_id'], 'talent_assess_person_cycle_uq');
                $table->index('performance_cycle_id', 'talent_assess_cycle_idx');
            });
        }

        // Succession plan: a critical seat (position/role) + its risk picture.
        if (! Schema::hasTable('succession_plans')) {
            Schema::create('succession_plans', function (Blueprint $table): void {
                $table->id();
                $table->string('role_title');
                $table->unsignedBigInteger('position_id')->nullable();
                $table->unsignedBigInteger('structure_id')->nullable();
                $table->unsignedBigInteger('incumbent_personnel_id')->nullable();
                $table->string('risk_of_loss')->default('medium'); // low · medium · high
                $table->string('impact_of_loss')->default('high');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('position_id', 'succession_plans_position_idx');
                $table->index('structure_id', 'succession_plans_structure_idx');
            });
        }

        // Successors lined up for a plan, with readiness horizon.
        if (! Schema::hasTable('succession_candidates')) {
            Schema::create('succession_candidates', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('succession_plan_id');
                $table->unsignedBigInteger('personnel_id');
                $table->string('readiness')->default('1_2_years'); // ready_now · 1_2_years · 3_5_years
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index('succession_plan_id', 'succession_cand_plan_idx');
                $table->foreign('succession_plan_id', 'succession_cand_plan_fk')
                    ->references('id')->on('succession_plans')->cascadeOnDelete();
            });
        }

        // Talent pools (HiPo / successor / critical-role bench).
        if (! Schema::hasTable('talent_pools')) {
            Schema::create('talent_pools', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('pool_type')->default('hipo'); // hipo · successor · critical_role
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('talent_pool_members')) {
            Schema::create('talent_pool_members', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('talent_pool_id');
                $table->unsignedBigInteger('personnel_id');
                $table->text('note')->nullable();
                $table->timestamps();

                $table->unique(['talent_pool_id', 'personnel_id'], 'talent_pool_member_uq');
                $table->foreign('talent_pool_id', 'talent_pool_members_pool_fk')
                    ->references('id')->on('talent_pools')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_pool_members');
        Schema::dropIfExists('talent_pools');
        Schema::dropIfExists('succession_candidates');
        Schema::dropIfExists('succession_plans');
        Schema::dropIfExists('talent_assessments');
    }
};
