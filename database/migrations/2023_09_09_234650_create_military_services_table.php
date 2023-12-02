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
        Schema::create('personnel_military_services', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no')->unique();
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->string('attitude_to_military_service');
            $table->integer('rank_id');
            $table->foreign('rank_id')->references('id')->on('ranks');
            $table->date('given_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_military_services');
    }
};
