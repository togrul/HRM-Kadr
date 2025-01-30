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
        Schema::create('rank_categories', function (Blueprint $table) {
            $table->integer('id')->primary()->index();
            $table->string('name');
            $table->integer('vacation_days_count')->default(0);
            $table->integer('contract_duration')->default(1);
            $table->integer('next_contract_duration')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_categories');
    }
};
