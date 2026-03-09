<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('leave_types', 'attendance_code')) {
                $table->string('attendance_code', 32)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table): void {
            if (Schema::hasColumn('leave_types', 'attendance_code')) {
                $table->dropColumn('attendance_code');
            }
        });
    }
};
