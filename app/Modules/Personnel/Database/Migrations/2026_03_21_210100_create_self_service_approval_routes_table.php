<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('self_service_approval_routes')) {
            return;
        }

        Schema::create('self_service_approval_routes', function (Blueprint $table): void {
            $table->id();
            $table->string('request_type', 32);
            $table->foreignId('personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->foreignId('structure_id')->nullable()->constrained('structures')->nullOnDelete();
            $table->integer('position_id')->nullable();
            $table->foreignId('approver_personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->foreignId('fallback_approver_personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->boolean('hr_always_included')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['request_type', 'personnel_id']);
            $table->index(['request_type', 'structure_id', 'position_id']);
            $table->index(['request_type', 'position_id']);
            $table->index(['request_type', 'is_active']);

            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_approval_routes');
    }
};
