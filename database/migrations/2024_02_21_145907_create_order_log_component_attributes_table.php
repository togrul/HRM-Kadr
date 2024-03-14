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
        Schema::create('order_log_component_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('order_no');
            $table->foreign('order_no')->references('order_no')->on('order_logs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignIdFor(\App\Models\Component::class)->constrained();
            $table->string('attribute_key');
            $table->string('attribute_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_log_component_attributes');
    }
};
