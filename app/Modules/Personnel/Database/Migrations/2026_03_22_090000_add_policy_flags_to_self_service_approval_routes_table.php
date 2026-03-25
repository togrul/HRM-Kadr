<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('self_service_approval_routes')) {
            return;
        }

        Schema::table('self_service_approval_routes', function (Blueprint $table): void {
            if (! Schema::hasColumn('self_service_approval_routes', 'include_primary_approver')) {
                $table->boolean('include_primary_approver')->default(true)->after('request_type');
            }

            if (! Schema::hasColumn('self_service_approval_routes', 'include_upper_approver')) {
                $table->boolean('include_upper_approver')->default(false)->after('include_primary_approver');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('self_service_approval_routes')) {
            return;
        }

        Schema::table('self_service_approval_routes', function (Blueprint $table): void {
            if (Schema::hasColumn('self_service_approval_routes', 'include_upper_approver')) {
                $table->dropColumn('include_upper_approver');
            }

            if (Schema::hasColumn('self_service_approval_routes', 'include_primary_approver')) {
                $table->dropColumn('include_primary_approver');
            }
        });
    }
};
