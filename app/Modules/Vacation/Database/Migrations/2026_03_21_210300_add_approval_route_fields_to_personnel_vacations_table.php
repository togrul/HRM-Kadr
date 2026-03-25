<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_vacations', function (Blueprint $table): void {
            if (! Schema::hasColumn('personnel_vacations', 'approver_personnel_id')) {
                $table->foreignId('approver_personnel_id')->nullable()->after('approval_status')->constrained('personnels')->nullOnDelete();
            }

            if (! Schema::hasColumn('personnel_vacations', 'fallback_approver_personnel_id')) {
                $table->foreignId('fallback_approver_personnel_id')->nullable()->after('approver_personnel_id')->constrained('personnels')->nullOnDelete();
            }

            if (! Schema::hasColumn('personnel_vacations', 'approval_route_source')) {
                $table->string('approval_route_source', 64)->nullable()->after('fallback_approver_personnel_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personnel_vacations', function (Blueprint $table): void {
            if (Schema::hasColumn('personnel_vacations', 'fallback_approver_personnel_id')) {
                $table->dropConstrainedForeignId('fallback_approver_personnel_id');
            }

            if (Schema::hasColumn('personnel_vacations', 'approver_personnel_id')) {
                $table->dropConstrainedForeignId('approver_personnel_id');
            }

            if (Schema::hasColumn('personnel_vacations', 'approval_route_source')) {
                $table->dropColumn('approval_route_source');
            }
        });
    }
};
