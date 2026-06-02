<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_lifecycle_events', function (Blueprint $table): void {
            $table->timestamp('last_reminder_at')->nullable()->after('completed_at');
            $table->unsignedSmallInteger('reminder_count')->default(0)->after('last_reminder_at');
        });

        Schema::table('employee_lifecycle_tasks', function (Blueprint $table): void {
            $table->timestamp('last_reminder_at')->nullable()->after('completed_at');
            $table->unsignedSmallInteger('reminder_count')->default(0)->after('last_reminder_at');
        });
    }

    public function down(): void
    {
        Schema::table('employee_lifecycle_tasks', function (Blueprint $table): void {
            $table->dropColumn(['last_reminder_at', 'reminder_count']);
        });

        Schema::table('employee_lifecycle_events', function (Blueprint $table): void {
            $table->dropColumn(['last_reminder_at', 'reminder_count']);
        });
    }
};
