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
        Schema::table('personnels', function (Blueprint $table) {
            $table->integer('social_origin_id')->nullable()->after('leave_work_date');
            $table->foreign('social_origin_id')->references('id')->on('social_origins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropColumn('social_origin_id');
        });
    }
};
