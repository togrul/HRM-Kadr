<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The HR side-effect an order type performs when approved: none | vacation |
 * termination | transfer | surname_change | hire. Each variable in the template's
 * `variables` JSON may carry an `effect_role` naming which structured input it feeds
 * (start_date, days, new_structure, …); approval runs the matching effect with those.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_word_templates', function (Blueprint $table) {
            $table->string('effect', 32)->default('none')->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('order_word_templates', function (Blueprint $table) {
            $table->dropColumn('effect');
        });
    }
};
