<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire the legacy "components" machinery. After the Word-upload engine took over order
 * generation and self-service vacation was decoupled from components, nothing reads or
 * writes these tables. Drop them and the order_log_personnels.component_id column.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Dependent tables / FKs first (they reference components).
        Schema::dropIfExists('order_log_component_attributes');
        Schema::dropIfExists('order_log_components');

        if (Schema::hasColumn('order_log_personnels', 'component_id')) {
            Schema::table('order_log_personnels', function (Blueprint $table) {
                $table->dropConstrainedForeignId('component_id');
            });
        }

        Schema::dropIfExists('components');
    }

    public function down(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_type_id')->nullable();
            $table->unsignedBigInteger('rank_id')->nullable();
            $table->string('name');
            $table->longText('content')->nullable();
            $table->string('title')->nullable();
            $table->json('dynamic_fields')->nullable();
            $table->timestamps();
        });

        Schema::table('order_log_personnels', function (Blueprint $table) {
            $table->foreignId('component_id')->nullable()->after('tabel_no');
        });

        Schema::create('order_log_components', function (Blueprint $table) {
            $table->id();
            $table->string('order_no');
            $table->foreignId('component_id');
            $table->integer('row_number')->nullable();
            $table->timestamps();
        });

        Schema::create('order_log_component_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('order_no');
            $table->foreignId('component_id');
            $table->json('attributes')->nullable();
            $table->integer('row_number')->nullable();
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->timestamps();
        });
    }
};
