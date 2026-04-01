<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (! Schema::hasColumn('leaves', 'fallback_approver_personnel_id')) {
                $table->foreignId('fallback_approver_personnel_id')->nullable()->after('assigned_to');
                $table->foreign('fallback_approver_personnel_id', 'leaves_fallback_approver_fk')
                    ->references('id')
                    ->on('personnels')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('leaves', 'approval_route_source')) {
                $table->string('approval_route_source', 64)->nullable()->after('fallback_approver_personnel_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            if (Schema::hasColumn('leaves', 'fallback_approver_personnel_id')) {
                $table->dropForeign('leaves_fallback_approver_fk');
                $table->dropColumn('fallback_approver_personnel_id');
            }

            if (Schema::hasColumn('leaves', 'approval_route_source')) {
                $table->dropColumn('approval_route_source');
            }
        });
    }
};
