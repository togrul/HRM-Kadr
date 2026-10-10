<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The people a multi-participant order (çoxşəxsli əmr — e.g. one business trip order for a
 * whole team) is about: one row per employee, in document order, with that person's own
 * field values (overrides of the shared ones) and what the approval effect did for them
 * (effect_state), so a reversal can undo it person by person. Single-person orders have no
 * rows here — their snapshot keeps the one personnel_id as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_participants')) {
            return;
        }

        Schema::create('order_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_log_id')->constrained('order_logs')->cascadeOnDelete();
            $table->unsignedBigInteger('personnel_id');
            $table->unsignedSmallInteger('position')->default(1);
            $table->json('fields')->nullable();
            $table->json('effect_state')->nullable();
            $table->timestamps();

            $table->unique(['order_log_id', 'personnel_id']);
            $table->index('personnel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_participants');
    }
};
