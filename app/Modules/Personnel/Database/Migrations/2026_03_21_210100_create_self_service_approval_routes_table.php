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
            $table->foreignId('personnel_id')->nullable();
            $table->foreignId('structure_id')->nullable();
            $table->integer('position_id')->nullable();
            $table->foreignId('approver_personnel_id')->nullable();
            $table->foreignId('fallback_approver_personnel_id')->nullable();
            $table->boolean('hr_always_included')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->index(['request_type', 'personnel_id'], 'ssar_req_personnel_idx');
            $table->index(['request_type', 'structure_id', 'position_id'], 'ssar_req_structure_position_idx');
            $table->index(['request_type', 'position_id'], 'ssar_req_position_idx');
            $table->index(['request_type', 'is_active'], 'ssar_req_active_idx');

            $table->foreign('personnel_id', 'ssar_personnel_fk')->references('id')->on('personnels')->nullOnDelete();
            $table->foreign('structure_id', 'ssar_structure_fk')->references('id')->on('structures')->nullOnDelete();
            $table->foreign('approver_personnel_id', 'ssar_approver_fk')->references('id')->on('personnels')->nullOnDelete();
            $table->foreign('fallback_approver_personnel_id', 'ssar_fallback_fk')->references('id')->on('personnels')->nullOnDelete();
            $table->foreign('created_by', 'ssar_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('position_id', 'ssar_position_fk')->references('id')->on('positions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_approval_routes');
    }
};
