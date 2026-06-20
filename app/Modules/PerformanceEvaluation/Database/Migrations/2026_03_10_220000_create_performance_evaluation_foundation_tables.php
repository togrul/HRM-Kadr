<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_cycles')) {
            Schema::create('performance_cycles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('cycle_type')->default('annual');
                $table->date('period_start');
                $table->date('period_end');
                $table->string('status')->default('draft');
                $table->boolean('auto_generate_forms')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('performance_form_templates')) {
            Schema::create('performance_form_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('performance_form_template_sections')) {
            Schema::create('performance_form_template_sections', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_form_template_id');
                $table->string('name');
                $table->decimal('weight_percent', 5, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->foreign('performance_form_template_id', 'pf_template_sections_template_fk')
                    ->references('id')
                    ->on('performance_form_templates')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('performance_form_template_items')) {
            Schema::create('performance_form_template_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_form_template_section_id');
                $table->unsignedBigInteger('training_competency_id')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('weight_percent', 5, 2)->default(0);
                $table->decimal('low_score_threshold', 5, 2)->default(60);
                $table->boolean('requires_comment')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->foreign('performance_form_template_section_id', 'pf_template_items_section_fk')
                    ->references('id')
                    ->on('performance_form_template_sections')
                    ->cascadeOnDelete();
                $table->foreign('training_competency_id', 'pf_template_items_competency_fk')
                    ->references('id')
                    ->on('training_competencies')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('performance_forms')) {
            Schema::create('performance_forms', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_cycle_id');
                $table->unsignedBigInteger('performance_form_template_id');
                $table->unsignedBigInteger('personnel_id');
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->unsignedBigInteger('hr_reviewer_id')->nullable();
                $table->string('self_status')->default('draft');
                $table->string('manager_status')->default('draft');
                $table->string('hr_status')->default('draft');
                $table->decimal('final_score', 6, 2)->nullable();
                $table->string('final_category')->nullable();
                $table->string('result_status')->default('draft');
                $table->timestamps();
                $table->foreign('performance_cycle_id', 'performance_forms_cycle_fk')
                    ->references('id')
                    ->on('performance_cycles')
                    ->cascadeOnDelete();
                $table->foreign('performance_form_template_id', 'performance_forms_template_fk')
                    ->references('id')
                    ->on('performance_form_templates')
                    ->cascadeOnDelete();
                $table->foreign('personnel_id', 'performance_forms_personnel_fk')
                    ->references('id')
                    ->on('personnels')
                    ->cascadeOnDelete();
                $table->foreign('manager_id', 'performance_forms_manager_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
                $table->foreign('hr_reviewer_id', 'performance_forms_hr_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('performance_form_scores')) {
            Schema::create('performance_form_scores', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_form_id');
                $table->unsignedBigInteger('performance_form_template_item_id');
                $table->string('evaluator_type');
                $table->decimal('score', 5, 2);
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->foreign('performance_form_id', 'performance_form_scores_form_fk')
                    ->references('id')
                    ->on('performance_forms')
                    ->cascadeOnDelete();
                $table->foreign('performance_form_template_item_id', 'performance_form_scores_item_fk')
                    ->references('id')
                    ->on('performance_form_template_items')
                    ->cascadeOnDelete();
                $table->unique(
                    ['performance_form_id', 'performance_form_template_item_id', 'evaluator_type'],
                    'performance_form_scores_unique'
                );
            });
        }

        if (! Schema::hasTable('performance_training_need_links')) {
            Schema::create('performance_training_need_links', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_form_id');
                $table->unsignedBigInteger('performance_form_score_id');
                $table->unsignedBigInteger('training_need_item_id');
                $table->unsignedBigInteger('training_competency_id')->nullable();
                $table->string('source')->default('low_score');
                $table->timestamps();
                $table->foreign('performance_form_id', 'performance_need_links_form_fk')
                    ->references('id')
                    ->on('performance_forms')
                    ->cascadeOnDelete();
                $table->foreign('performance_form_score_id', 'performance_need_links_score_fk')
                    ->references('id')
                    ->on('performance_form_scores')
                    ->cascadeOnDelete();
                $table->foreign('training_need_item_id', 'performance_need_links_training_need_fk')
                    ->references('id')
                    ->on('training_need_items')
                    ->cascadeOnDelete();
                $table->foreign('training_competency_id', 'performance_need_links_competency_fk')
                    ->references('id')
                    ->on('training_competencies')
                    ->nullOnDelete();
                $table->unique(['performance_form_score_id'], 'performance_training_need_score_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_training_need_links');
        Schema::dropIfExists('performance_form_scores');
        Schema::dropIfExists('performance_forms');
        Schema::dropIfExists('performance_form_template_items');
        Schema::dropIfExists('performance_form_template_sections');
        Schema::dropIfExists('performance_form_templates');
        Schema::dropIfExists('performance_cycles');
    }
};
