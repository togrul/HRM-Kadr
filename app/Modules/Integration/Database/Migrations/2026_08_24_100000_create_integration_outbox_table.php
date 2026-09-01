<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transactional outbox: domain events waiting to be read by the finance system.
 *
 * ## Why a table and not an HTTP call
 *
 * The row is written **inside the same transaction as the domain change**. If
 * the order transition rolls back, the event rolls back with it. Sending over
 * the wire at the moment of approval cannot offer that: a transaction that
 * failed afterwards would leave the counterpart holding a fact that never
 * happened, and nothing would ever correct it.
 *
 * It also survives the counterpart being down, which is the normal case at
 * month-end when both systems are busy.
 *
 * ## The primary key IS the cursor
 *
 * No separate `sequence` column: `id` is already monotonic and gap-tolerant, and
 * computing `max(sequence) + 1` would race two concurrent approvals into the
 * same number. The reader passes the last id it applied.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_outbox', function (Blueprint $table): void {
            $table->id();
            $table->string('topic', 40);
            // What the event is about (order_no, tabel_no, …) — for humans
            // reading the table, and for de-duplicating a replay by hand.
            $table->string('entity_key', 64)->nullable();
            $table->json('payload');
            $table->timestamp('created_at')->nullable();

            $table->index(['topic', 'id'], 'integration_outbox_feed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outbox');
    }
};
