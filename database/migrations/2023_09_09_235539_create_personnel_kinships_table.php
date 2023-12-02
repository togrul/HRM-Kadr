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
        Schema::create('personnel_kinships', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('kinship_id');
            $table->foreign('kinship_id')->references('id')->on('kinships');
            $table->string('fullname');
            $table->date('birthdate');
            $table->string('company_name')->nullable();
            $table->string('position')->nullable();
            $table->string('registered_address');
            $table->string('residental_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_kinships');
    }
};
