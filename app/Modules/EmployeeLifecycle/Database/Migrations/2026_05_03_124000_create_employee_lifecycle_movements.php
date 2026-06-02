<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_lifecycle_movements')) {
            Schema::create('employee_lifecycle_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('employee_lifecycle_events')->cascadeOnDelete();
                $table->foreignId('personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
                $table->string('tabel_no')->nullable()->index();
                $table->string('movement_type')->index();
                $table->foreignId('current_structure_id')->nullable()->constrained('structures')->nullOnDelete();
                $table->integer('current_position_id')->nullable();
                $table->foreignId('target_structure_id')->nullable()->constrained('structures')->nullOnDelete();
                $table->integer('target_position_id')->nullable();
                $table->date('effective_date')->index();
                $table->string('status')->default('planned')->index();
                $table->text('reason')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->foreign('current_position_id', 'el_move_current_pos_fk')->references('id')->on('positions')->nullOnDelete();
                $table->foreign('target_position_id', 'el_move_target_pos_fk')->references('id')->on('positions')->nullOnDelete();
                $table->index(['status', 'effective_date'], 'el_movements_status_date_idx');
                $table->index(['movement_type', 'status'], 'el_movements_type_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_lifecycle_movements');
    }
};
