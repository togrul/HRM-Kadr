<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_template_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_type_id')->constrained('order_types')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique('order_type_id');
        });

        Schema::create('order_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_template_set_id')->constrained('order_template_sets')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('template_name')->nullable();
            $table->string('template_path', 1024);
            $table->string('checksum', 128)->nullable();
            $table->string('status', 32)->default('draft');
            $table->boolean('is_active')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_template_set_id', 'version_no'], 'order_template_versions_set_version_unique');
            $table->index(['order_template_set_id', 'is_active'], 'order_template_versions_set_active_index');
            $table->index(['status', 'published_at'], 'order_template_versions_status_published_index');
        });

        Schema::create('order_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_template_version_id')->constrained('order_template_versions')->cascadeOnDelete();
            $table->string('field_key');
            $table->string('label');
            $table->string('field_type', 64);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('default_value')->nullable();
            $table->json('data_source')->nullable();
            $table->json('ui_config')->nullable();
            $table->json('transform_config')->nullable();
            $table->json('validation_config')->nullable();
            $table->timestamps();

            $table->unique(['order_template_version_id', 'field_key'], 'order_template_fields_version_key_unique');
            $table->index(['order_template_version_id', 'sort_order'], 'order_template_fields_version_sort_index');
        });

        Schema::create('order_template_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_template_version_id')->constrained('order_template_versions')->cascadeOnDelete();
            $table->string('placeholder');
            $table->string('field_key');
            $table->string('scope', 32)->default('scalar');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('mapping_config')->nullable();
            $table->timestamps();

            $table->unique(
                ['order_template_version_id', 'placeholder', 'scope'],
                'order_template_mappings_version_placeholder_scope_unique'
            );
            $table->index(['order_template_version_id', 'sort_order'], 'order_template_mappings_version_sort_index');
        });

        Schema::create('order_template_version_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_template_version_id')->constrained('order_template_versions')->cascadeOnDelete();
            $table->string('action', 64);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_template_version_id', 'created_at'], 'order_template_version_audits_version_created_index');
        });

        Schema::create('order_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('render_id')->nullable()->unique();
            $table->foreignId('order_log_id')->nullable()->constrained('order_logs')->nullOnDelete();
            $table->foreignId('order_type_id')->nullable()->constrained('order_types')->nullOnDelete();
            $table->foreignId('order_template_version_id')->nullable()->constrained('order_template_versions')->nullOnDelete();
            $table->string('status', 32)->default('started');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('output_path', 1024)->nullable();
            $table->text('error_message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['status', 'created_at'], 'order_generation_logs_status_created_index');
            $table->index(['order_type_id', 'created_at'], 'order_generation_logs_type_created_index');
            $table->index('order_log_id', 'order_generation_logs_order_log_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_generation_logs');
        Schema::dropIfExists('order_template_version_audits');
        Schema::dropIfExists('order_template_mappings');
        Schema::dropIfExists('order_template_fields');
        Schema::dropIfExists('order_template_versions');
        Schema::dropIfExists('order_template_sets');
    }
};
