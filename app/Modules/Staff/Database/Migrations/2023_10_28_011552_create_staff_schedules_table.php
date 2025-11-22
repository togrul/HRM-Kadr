<?php

use App\Models\Structure;
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
        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Structure::class)->constrained();
            $table->integer('position_id')->nullable();
            $table->foreign('position_id')->references('id')->on('positions');
            $table->double('total', 8, 2);
            $table->double('filled', 8, 2);
            $table->double('vacant', 8, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_schedules');
    }
};
