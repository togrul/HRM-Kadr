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
        Schema::table('rank_categories', function (Blueprint $table) {
            $table->float('vacation_days_per_month', 8, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rank_categories', function (Blueprint $table) {
            $table->dropColumn('vacation_days_per_month');
        });
    }
};
