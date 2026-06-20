<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_manual_entries', function (Blueprint $table): void {
            $table->time('check_in_at')->nullable()->after('overtime_minutes');
            $table->time('check_out_at')->nullable()->after('check_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_manual_entries', function (Blueprint $table): void {
            $table->dropColumn(['check_in_at', 'check_out_at']);
        });
    }
};

