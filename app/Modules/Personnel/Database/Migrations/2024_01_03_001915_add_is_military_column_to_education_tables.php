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
            $table->boolean('is_military')->default(false);
        });

        Schema::table('personnel_extra_education', function (Blueprint $table) {
            $table->boolean('is_military')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel_education', function (Blueprint $table) {
            $table->dropColumn('is_military');
        });

        Schema::table('personnel_extra_education', function (Blueprint $table) {
            $table->dropColumn('is_military');
        });
    }
};
