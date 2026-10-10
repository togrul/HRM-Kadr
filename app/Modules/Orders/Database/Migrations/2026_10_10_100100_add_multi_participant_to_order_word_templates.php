<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Word template marked «çoxşəxsli» is issued for several employees at once: the composer
 * collects a participant list and the document repeats a table row (or a marked block) per
 * person. Off by default, so every existing template keeps working as a single-person order.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_word_templates', 'order_word_template_versions'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'multi_participant')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->boolean('multi_participant')->default(false);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['order_word_templates', 'order_word_template_versions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'multi_participant')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('multi_participant');
                });
            }
        }
    }
};
