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
        Schema::create('personnel_education_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')
                ->references('tabel_no')
                ->on('personnels')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('education_place');
            $table->string('specialty');
            $table->text('description')->nullable();
            $table->date('request_date');
            $table->string('request_result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_education_requests');
    }
};
