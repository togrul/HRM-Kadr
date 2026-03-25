<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_vacations', function (Blueprint $table): void {
            $table->string('approval_status', 32)->nullable()->after('remaining_days');
            $table->string('submission_source', 32)->nullable()->after('approval_status');
            $table->foreignId('submitted_by_user_id')->nullable()->after('submission_source')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personnel_vacations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by_user_id');
            $table->dropColumn(['submission_source', 'approval_status']);
        });
    }
};
