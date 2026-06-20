<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_document_assignments', function (Blueprint $table): void {
            $table->timestamp('last_reminder_at')->nullable()->after('due_at');
            $table->index('last_reminder_at');
        });

        Schema::table('employee_content_assignments', function (Blueprint $table): void {
            $table->timestamp('last_reminder_at')->nullable()->after('due_at');
            $table->index('last_reminder_at');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_document_assignments', function (Blueprint $table): void {
            $table->dropIndex(['last_reminder_at']);
            $table->dropColumn('last_reminder_at');
        });

        Schema::table('employee_content_assignments', function (Blueprint $table): void {
            $table->dropIndex(['last_reminder_at']);
            $table->dropColumn('last_reminder_at');
        });
    }
};
