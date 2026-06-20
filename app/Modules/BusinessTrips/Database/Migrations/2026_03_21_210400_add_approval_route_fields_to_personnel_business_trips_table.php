<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_business_trips', function (Blueprint $table): void {
            if (! Schema::hasColumn('personnel_business_trips', 'approver_personnel_id')) {
                $table->foreignId('approver_personnel_id')->nullable()->after('approval_status');
                $table->foreign('approver_personnel_id', 'pbt_approver_fk')
                    ->references('id')
                    ->on('personnels')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('personnel_business_trips', 'fallback_approver_personnel_id')) {
                $table->foreignId('fallback_approver_personnel_id')->nullable()->after('approver_personnel_id');
                $table->foreign('fallback_approver_personnel_id', 'pbt_fallback_fk')
                    ->references('id')
                    ->on('personnels')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('personnel_business_trips', 'approval_route_source')) {
                $table->string('approval_route_source', 64)->nullable()->after('fallback_approver_personnel_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personnel_business_trips', function (Blueprint $table): void {
            if (Schema::hasColumn('personnel_business_trips', 'fallback_approver_personnel_id')) {
                $table->dropForeign('pbt_fallback_fk');
                $table->dropColumn('fallback_approver_personnel_id');
            }

            if (Schema::hasColumn('personnel_business_trips', 'approver_personnel_id')) {
                $table->dropForeign('pbt_approver_fk');
                $table->dropColumn('approver_personnel_id');
            }

            if (Schema::hasColumn('personnel_business_trips', 'approval_route_source')) {
                $table->dropColumn('approval_route_source');
            }
        });
    }
};
