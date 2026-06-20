<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * History of a Word template's master files. Each time the designer uploads a new .docx
 * for an existing type, the previous master + its variable mapping are archived here so
 * older versions can be reviewed or downloaded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_word_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_word_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('label');
            $table->string('effect', 32)->default('none');
            $table->string('docx_path');
            $table->json('variables')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['order_word_template_id', 'version'], 'owtv_template_version_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_word_template_versions');
    }
};
