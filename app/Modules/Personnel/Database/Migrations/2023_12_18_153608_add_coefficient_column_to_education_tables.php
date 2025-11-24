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
            $table->double('coefficient', 4, 2)->nullable();
        });

        Schema::table('personnel_extra_education', function (Blueprint $table) {
            $table->double('coefficient', 4, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel_education', function (Blueprint $table) {
            $table->dropColumn('coefficient');
        });

        Schema::table('personnel_extra_education', function (Blueprint $table) {
            $table->dropColumn('coefficient');
        });
    }
};
