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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('leave_type_id')
                ->nullable() // This is required for nullOnDelete()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('total_days')->nullable();
            $table->text('reason')->nullable();
            $table->integer('status_id');
            $table->foreign('status_id')->references('id')->on('order_statuses');
            $table->string('document_path')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
