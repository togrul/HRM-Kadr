<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_lifecycle_plan_templates')) {
            Schema::create('employee_lifecycle_plan_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('onboarding')->index();
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('default_duration_days')->default(14);
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['type', 'is_active']);
            });
        }

        if (! Schema::hasTable('employee_lifecycle_task_templates')) {
            Schema::create('employee_lifecycle_task_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_template_id')->constrained('employee_lifecycle_plan_templates')->cascadeOnDelete();
                $table->string('title');
                $table->string('owner_type')->default('hr')->index();
                $table->unsignedSmallInteger('due_offset_days')->default(0);
                $table->boolean('is_required')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['plan_template_id', 'sort_order'], 'el_task_tpl_plan_sort_idx');
            });
        }

        if (! Schema::hasColumn('employee_lifecycle_events', 'plan_template_id')) {
            Schema::table('employee_lifecycle_events', function (Blueprint $table) {
                $table->foreignId('plan_template_id')
                    ->nullable()
                    ->after('owner_user_id')
                    ->constrained('employee_lifecycle_plan_templates')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('employee_lifecycle_tasks', 'task_template_id')) {
            Schema::table('employee_lifecycle_tasks', function (Blueprint $table) {
                $table->foreignId('task_template_id')
                    ->nullable()
                    ->after('event_id')
                    ->constrained('employee_lifecycle_task_templates')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('employee_lifecycle_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_template_id');
        });

        Schema::table('employee_lifecycle_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_template_id');
        });

        Schema::dropIfExists('employee_lifecycle_task_templates');
        Schema::dropIfExists('employee_lifecycle_plan_templates');
    }
};
