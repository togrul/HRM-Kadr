<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the unused order_template_previews table. Preview rows were written by a dev
 * seeder but never read by any runtime or UI code (dead write-only sink), so the
 * table is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('order_template_previews');
    }

    public function down(): void
    {
        // Intentionally not recreated — the table is obsolete.
    }
};
