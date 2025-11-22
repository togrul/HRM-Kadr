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
        Schema::table('components', function (Blueprint $table) {
            $table->integer('rank_id')->index()->after('order_type_id')->default(10)->nullable();
            $table->foreign('rank_id')->references('id')->on('ranks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropForeign('rank_id');
            $table->dropColumn('rank_id');
        });
    }
};
