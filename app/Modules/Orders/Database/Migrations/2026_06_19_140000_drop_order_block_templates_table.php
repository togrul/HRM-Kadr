<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the retired block-engine template table. Authoring moved to the Word-upload
 * engine (order_word_templates); the block authoring subsystem and its model were
 * removed. Already-issued block orders still reprint from their order_logs snapshots,
 * which don't depend on this table.
 */
return new class extends Migration
{
    public function down(): void
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

    public function up(): void
    {
        Schema::dropIfExists('order_block_templates');
    }
};
