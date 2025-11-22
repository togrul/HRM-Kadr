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
        Schema::table('personnel_vacations', function (Blueprint $table) {
            $table->integer('vacation_days_total')->default(0)->after('order_date');
            $table->integer('remaining_days')->default(0)->after('order_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel_vacations', function (Blueprint $table) {
            $table->dropColumn(['remaining_days', 'vacation_days_total']);
        });
    }
};
