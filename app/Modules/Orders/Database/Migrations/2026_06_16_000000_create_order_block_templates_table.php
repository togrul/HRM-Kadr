<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clean block-based order templates for the redesigned engine. A template is just a
 * code, a label, its ordered blocks and its derived field schema — persisted as JSON
 * so the designer can author and the composer can load them without the legacy
 * template tables (strangler: legacy retired later).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_block_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->json('blocks');
            $table->json('fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_block_templates');
    }
};
