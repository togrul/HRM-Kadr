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
        Schema::table('ranks', function (Blueprint $table) {
            $table->integer('rank_category_id')->index()->nullable()->after('id');
            $table->foreign('rank_category_id')->references('id')->on('rank_categories');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->integer('rank_category_id')->index()->nullable()->after('id');
            $table->foreign('rank_category_id')->references('id')->on('rank_categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $table->dropForeign('rank_category_id');
            $table->dropColumn('rank_category_id');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign('rank_category_id');
            $table->dropColumn('rank_category_id');
        });
    }
};
