<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_logs', 'signatory_personnel_id')) {
                $table->unsignedBigInteger('signatory_personnel_id')->nullable()->after('given_by_rank');
                $table->index('signatory_personnel_id', 'order_logs_signatory_personnel_idx');
            }

            if (! Schema::hasColumn('order_logs', 'signatory_snapshot')) {
                $table->json('signatory_snapshot')->nullable()->after('signatory_personnel_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('order_logs', 'signatory_personnel_id')) {
                $table->dropIndex('order_logs_signatory_personnel_idx');
                $table->dropColumn('signatory_personnel_id');
            }

            if (Schema::hasColumn('order_logs', 'signatory_snapshot')) {
                $table->dropColumn('signatory_snapshot');
            }
        });
    }
};
