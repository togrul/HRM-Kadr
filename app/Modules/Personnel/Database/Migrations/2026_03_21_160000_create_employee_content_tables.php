<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_content_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('content_type', 32);
            $table->text('description')->nullable();
            $table->string('storage_disk', 64)->nullable();
            $table->string('storage_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('visibility', 32)->default('internal');
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_content_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('employee_content_assets')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at');
            $table->dateTime('due_at')->nullable();
            $table->string('status', 32)->default('assigned');
            $table->timestamps();

            $table->index(['personnel_id', 'status']);
        });

        Schema::create('employee_content_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained('employee_content_assignments')->cascadeOnDelete();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedTinyInteger('watch_progress_percent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_content_views');
        Schema::dropIfExists('employee_content_assignments');
        Schema::dropIfExists('employee_content_assets');
    }
};
