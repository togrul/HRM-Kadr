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
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->string('order_no')->unique();
            $table->dateTime('given_date');
            $table->string('given_by');
            $table->integer('status_id');
            $table->foreign('status_id')->references('id')->on('order_statuses');
            $table->foreignId('creator_id')->references('id')->on('users');
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
        Schema::dropIfExists('order_logs');
    }
};
