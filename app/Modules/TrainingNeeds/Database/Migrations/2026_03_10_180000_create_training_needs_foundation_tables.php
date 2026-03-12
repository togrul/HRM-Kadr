<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_competency_groups')) {
            Schema::create('training_competency_groups', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('training_levels')) {
            Schema::create('training_levels', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->unsignedSmallInteger('score');
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->unique(['name']);
                $table->unique(['score']);
            });
        }

        if (! Schema::hasTable('training_competencies')) {
            Schema::create('training_competencies', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('training_competency_group_id')->nullable()->constrained('training_competency_groups')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_mandatory')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('training_programs')) {
            Schema::create('training_programs', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('code')->nullable();
                $table->enum('delivery_type', ['internal', 'external', 'hybrid'])->default('internal');
                $table->decimal('duration_hours', 8, 2)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('training_program_competency_map')) {
            Schema::create('training_program_competency_map', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('training_program_id')->constrained('training_programs')->cascadeOnDelete();
                $table->foreignId('training_competency_id')->constrained('training_competencies')->cascadeOnDelete();
                $table->foreignId('target_level_id')->nullable()->constrained('training_levels')->nullOnDelete();
                $table->timestamps();
                $table->unique(['training_program_id', 'training_competency_id'], 'training_program_competency_unique');
            });
        }

        if (! Schema::hasTable('role_competency_requirements')) {
            Schema::create('role_competency_requirements', function (Blueprint $table): void {
                $table->id();
                $table->integer('position_id');
                $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();
                $table->foreignId('training_competency_id')->constrained('training_competencies')->cascadeOnDelete();
                $table->foreignId('required_level_id')->constrained('training_levels')->cascadeOnDelete();
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->boolean('is_mandatory')->default(false);
                $table->timestamps();
                $table->unique(['position_id', 'training_competency_id'], 'role_competency_requirement_unique');
            });
        }

        if (! Schema::hasTable('employee_competency_profiles')) {
            Schema::create('employee_competency_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
                $table->foreignId('training_competency_id')->constrained('training_competencies')->cascadeOnDelete();
                $table->foreignId('current_level_id')->nullable()->constrained('training_levels')->nullOnDelete();
                $table->string('source')->nullable();
                $table->timestamp('last_assessed_at')->nullable();
                $table->timestamps();
                $table->unique(['personnel_id', 'training_competency_id'], 'employee_competency_profile_unique');
            });
        }

        if (! Schema::hasTable('training_need_items')) {
            Schema::create('training_need_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
                $table->foreignId('training_competency_id')->constrained('training_competencies')->cascadeOnDelete();
                $table->integer('position_id')->nullable();
                $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
                $table->foreignId('recommended_program_id')->nullable()->constrained('training_programs')->nullOnDelete();
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->string('source')->nullable();
                $table->string('status')->default('draft');
                $table->text('reason')->nullable();
                $table->timestamps();
                $table->index(['status', 'priority']);
            });
        }

        if (Schema::hasTable('training_levels') && DB::table('training_levels')->count() === 0) {
            DB::table('training_levels')->insert([
                ['name' => 'Beginner', 'score' => 1, 'description' => 'Initial awareness level', 'sort_order' => 1, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Elementary', 'score' => 2, 'description' => 'Basic execution level', 'sort_order' => 2, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Intermediate', 'score' => 3, 'description' => 'Independent working level', 'sort_order' => 3, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Advanced', 'score' => 4, 'description' => 'Strong and repeatable execution level', 'sort_order' => 4, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Expert', 'score' => 5, 'description' => 'Expert and mentoring level', 'sort_order' => 5, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_need_items');
        Schema::dropIfExists('employee_competency_profiles');
        Schema::dropIfExists('role_competency_requirements');
        Schema::dropIfExists('training_program_competency_map');
        Schema::dropIfExists('training_programs');
        Schema::dropIfExists('training_competencies');
        Schema::dropIfExists('training_levels');
        Schema::dropIfExists('training_competency_groups');
    }
};
