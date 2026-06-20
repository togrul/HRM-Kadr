<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow order_logs to be created by the new block engine without the legacy
 * order/order_type rows. order_id and order_type_id become nullable so a
 * block-engine order (template_render_mode = block_v2, content frozen in
 * template_snapshot) can persist in the same list. Existing rows keep their values;
 * this is purely additive (strangler — legacy linkage retired later).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_logs', function (Blueprint $table) {
            // Keep the original column TYPES (order_id was a signed integer); only
            // toggle nullability so the foreign keys stay compatible.
            $table->integer('order_id')->nullable()->change();
            $table->unsignedBigInteger('order_type_id')->nullable()->change();
        });

        // The personnel pivot required a component_id (legacy component model); the
        // block engine has no components, so allow attaching personnel without one.
        Schema::table('order_log_personnels', function (Blueprint $table) {
            $table->unsignedBigInteger('component_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            $table->unsignedBigInteger('order_type_id')->nullable(false)->change();
        });

        Schema::table('order_log_personnels', function (Blueprint $table) {
            $table->unsignedBigInteger('component_id')->nullable(false)->change();
        });
    }
};
