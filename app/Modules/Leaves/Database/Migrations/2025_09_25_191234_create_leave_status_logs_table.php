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
        Schema::create('leave_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')->constrained()->cascadeOnDelete();
            $table->integer('status_id');
            $table->foreign('status_id')->references('id')->on('order_statuses');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('changed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_status_logs');
    }
};
