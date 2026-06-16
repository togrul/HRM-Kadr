<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_template_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('order_template_versions', 'render_mode')) {
                $table->string('render_mode', 32)->default('metadata')->after('template_path')->index('otv_render_mode_idx');
            }
        });

        Schema::create('order_template_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_template_version_id')->constrained('order_template_versions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('order_template_blocks')->nullOnDelete();
            $table->string('block_key', 96);
            $table->string('block_type', 48);
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_repeatable')->default(false);
            $table->json('condition_config')->nullable();
            $table->json('layout_config')->nullable();
            $table->json('data_config')->nullable();
            $table->timestamps();

            $table->unique(['order_template_version_id', 'block_key'], 'otb_version_key_unique');
            $table->index(['order_template_version_id', 'sort_order'], 'otb_version_sort_idx');
            $table->index('block_type', 'otb_type_idx');
        });

        Schema::create('order_template_block_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_template_block_id')->constrained('order_template_blocks')->cascadeOnDelete();
            $table->string('variable_key', 128);
            $table->string('label')->nullable();
            $table->text('fallback_value')->nullable();
            $table->json('format_config')->nullable();
            $table->timestamps();

            $table->unique(['order_template_block_id', 'variable_key'], 'otbv_block_variable_unique');
            $table->index('variable_key', 'otbv_variable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_template_block_variables');
        Schema::dropIfExists('order_template_blocks');

        Schema::table('order_template_versions', function (Blueprint $table) {
            if (Schema::hasColumn('order_template_versions', 'render_mode')) {
                $table->dropIndex('otv_render_mode_idx');
                $table->dropColumn('render_mode');
            }
        });
    }
};
