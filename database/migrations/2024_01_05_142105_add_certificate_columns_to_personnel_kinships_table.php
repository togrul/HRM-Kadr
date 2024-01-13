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
        Schema::table('personnel_kinships', function (Blueprint $table) {
            $table->string('birth_certificate_number')->nullable();
            $table->string('marriage_certificate_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel_kinships', function (Blueprint $table) {
            $table->dropColumn(['birth_certificate_number','marriage_certificate_number']);
        });
    }
};
