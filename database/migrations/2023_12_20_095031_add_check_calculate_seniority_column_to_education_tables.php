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
        Schema::table('personnel_education', function (Blueprint $table) {
            $table->boolean('calculate_as_seniority')->default(false);
        });

        Schema::table('personnel_extra_education', function (Blueprint $table) {
            $table->boolean('calculate_as_seniority')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel_education', function (Blueprint $table) {
            $table->dropColumn('calculate_as_seniority');
        });

        Schema::table('personnel_extra_education', function (Blueprint $table) {
            $table->dropColumn('calculate_as_seniority');
        });
    }
};
