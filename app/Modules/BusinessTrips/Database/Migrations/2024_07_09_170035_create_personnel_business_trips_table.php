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
        Schema::create('personnel_business_trips', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')
                ->references('tabel_no')
                ->on('personnels')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->text('location');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('order_given_by');
            $table->string('order_no')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->foreignId('added_by')->references('id')->on('users');
            $table->foreignId('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_business_trips');
    }
};
