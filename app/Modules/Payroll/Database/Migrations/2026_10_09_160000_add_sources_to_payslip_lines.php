<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The records a payslip line was built from (e.g. the rest-day work requests and the
 * substitution rows behind an order-derived earning), so locking can refuse a run whose
 * order facts changed after its calculation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payslip_lines') || Schema::hasColumn('payslip_lines', 'sources')) {
            return;
        }

        Schema::table('payslip_lines', function (Blueprint $table): void {
            $table->json('sources')->nullable()->after('sort');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payslip_lines', 'sources')) {
            return;
        }

        Schema::table('payslip_lines', function (Blueprint $table): void {
            $table->dropColumn('sources');
        });
    }
};
