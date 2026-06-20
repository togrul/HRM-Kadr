<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Word-upload order templates. The author prepares the whole order (əmr) in MS Word,
 * marking dynamic parts with [bracket] placeholders; on save we normalize the file to
 * a ${token} master (stored on the local disk at docx_path) and persist the detected
 * placeholders and how each maps to data in `variables`. The composer fills the tokens
 * with resolved data (employee/system) or per-order manual input to produce the .docx.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_word_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            // Relative path on the 'local' disk to the normalized ${token} master .docx.
            $table->string('docx_path');
            // Detected placeholders + their mapping: [{token,label,source,auto_key,field}].
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_word_templates');
    }
};
