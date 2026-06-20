<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_overtime_requests', function (Blueprint $table): void {
            $table->string('source', 32)
                ->default('auto_ledger')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_overtime_requests', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
    }
};
