<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_manual_entries', function (Blueprint $table): void {
            $table->string('calculation_shift_source', 32)
                ->nullable()
                ->after('early_leave_minutes');
            $table->foreignId('calculation_shift_id')
                ->nullable()
                ->after('calculation_shift_source')
                ->constrained('attendance_shifts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_manual_entries', function (Blueprint $table): void {
            $table->dropForeign(['calculation_shift_id']);
            $table->dropColumn(['calculation_shift_source', 'calculation_shift_id']);
        });
    }
};
