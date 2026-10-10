<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The immutable final copy of an approved order: the PDF rendered from its final .docx
 * at approval, with its SHA-256 so any later tampering with the stored file shows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_logs', 'final_pdf_path')) {
                $table->string('final_pdf_path')->nullable();
            }

            if (! Schema::hasColumn('order_logs', 'final_pdf_sha256')) {
                $table->char('final_pdf_sha256', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_logs', function (Blueprint $table): void {
            $table->dropColumn(['final_pdf_path', 'final_pdf_sha256']);
        });
    }
};
