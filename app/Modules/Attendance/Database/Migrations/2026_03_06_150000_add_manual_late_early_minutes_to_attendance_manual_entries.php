<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_manual_entries', function (Blueprint $table): void {
            $table->unsignedSmallInteger('late_minutes')->default(0)->after('overtime_minutes');
            $table->unsignedSmallInteger('early_leave_minutes')->default(0)->after('late_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_manual_entries', function (Blueprint $table): void {
            $table->dropColumn(['late_minutes', 'early_leave_minutes']);
        });
    }
};

