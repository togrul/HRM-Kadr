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
        Schema::create('personnel_labor_activities', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')
                ->references('tabel_no')
                ->on('personnels')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('company_name');
            $table->string('position');
            $table->double('coefficient', 4, 2)->nullable();
            $table->date('join_date');
            $table->date('leave_date')->nullable();
            $table->boolean('is_special_service')->default(false);
            $table->string('order_given_by')->nullable();
            $table->string('order_no')->nullable();
            $table->dateTime('order_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_labor_activities');
    }
};
