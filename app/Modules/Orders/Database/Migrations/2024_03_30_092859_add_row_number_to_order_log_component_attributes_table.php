<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('order_log_component_attributes')) {
            return;
        }

        Schema::table('order_log_component_attributes', function (Blueprint $table) {
            $table->integer('row_number')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('order_log_component_attributes')) {
            return;
        }

        Schema::table('order_log_component_attributes', function (Blueprint $table) {
            $table->dropColumn('row_number');
        });
    }
};
