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
        Schema::table('personnel_labor_activities', function (Blueprint $table) {
            $table->boolean('is_special_service')->default(false);
            $table->string('order_given_by')->nullable();
            $table->string('order_no')->nullable();
            $table->dateTime('order_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel_labor_activities', function (Blueprint $table) {
            $table->dropColumn(['is_special_service','order_given_by','order_no','order_date']);
        });
    }
};
