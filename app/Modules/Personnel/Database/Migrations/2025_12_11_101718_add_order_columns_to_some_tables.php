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
        Schema::table('personnel_punishments', function (Blueprint $table) {
            $table->string('order_given_by')->nullable();
            $table->string('order_no')->nullable();
            $table->dateTime('order_date')->nullable();
        });

         Schema::table('personnel_military_services', function (Blueprint $table) {
            $table->string('location')->nullable();
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel_punishments', function (Blueprint $table) {
            $table->dropColumn(['order_date', 'order_no', 'order_given_by']);
        });

        Schema::table('personnel_military_services', function (Blueprint $table) {
            $table->dropColumn('location');
         });
    }
};
