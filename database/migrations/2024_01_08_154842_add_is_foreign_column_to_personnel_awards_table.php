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
        Schema::table('awards', function (Blueprint $table) {
            $table->boolean('is_foreign')->default(false);
        });

        Schema::table('personnel_awards', function (Blueprint $table) {
            $table->boolean('is_old')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->dropColumn(['is_foreign']);
        });

        Schema::table('personnel_awards', function (Blueprint $table) {
            $table->dropColumn(['is_old']);
        });
    }
};
