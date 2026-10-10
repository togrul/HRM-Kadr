<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rest-day / holiday work called in by an order carries the compensation the order chose
 * (double_pay | day_off) as a column, so payroll and the finance feed read it instead of
 * parsing the reason text. Order rows recorded before this column existed are backfilled
 * from the reason the order wrote (its "another day off" wording in az or en); the rest
 * take the order's default, double pay.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_overtime_requests') || Schema::hasColumn('attendance_overtime_requests', 'compensation')) {
            return;
        }

        Schema::table('attendance_overtime_requests', function (Blueprint $table): void {
            $table->string('compensation', 16)->nullable()->after('source');
        });

        DB::table('attendance_overtime_requests')
            ->where('source', 'order')
            ->where(fn ($query) => $query
                ->where('reason', 'like', '%başqa istirahət günü verilsin%')
                ->orWhere('reason', 'like', '%another day off granted%'))
            ->update(['compensation' => 'day_off']);

        DB::table('attendance_overtime_requests')
            ->where('source', 'order')
            ->whereNull('compensation')
            ->update(['compensation' => 'double_pay']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('attendance_overtime_requests', 'compensation')) {
            return;
        }

        Schema::table('attendance_overtime_requests', function (Blueprint $table): void {
            $table->dropColumn('compensation');
        });
    }
};
