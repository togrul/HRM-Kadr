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
        Schema::create('personnel_master_degrees', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')
                ->references('tabel_no')
                ->on('personnels')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('degree')->default(3);
            $table->date('approved_date');
            $table->date('given_date');
            $table->date('redemption_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_master_degrees');
    }
};
