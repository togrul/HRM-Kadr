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
        Schema::create('vacations', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')
                ->references('tabel_no')
                ->on('personnels')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->smallInteger('reserved_date_month')->nullable();
            $table->integer('vacation_days_total')->default(0);
            $table->integer('remaining_days')->default(0);
            $table->integer('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacations');
    }
};
