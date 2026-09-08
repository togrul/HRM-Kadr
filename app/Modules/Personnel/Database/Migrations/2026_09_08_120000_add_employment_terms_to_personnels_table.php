<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contract and working-time terms captured when an employee is hired.
 *
 * The two JSON columns hold values that only exist for some of the work
 * schedules — lunch hours and rest days for a 5/6-day week, shift hours for a
 * shift rota. Spreading them over sixteen mostly-empty time columns would buy
 * nothing: nothing filters or joins on an individual shift boundary, the whole
 * set is read and written together with the personnel record, and the shape can
 * grow a fifth shift without another migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table): void {
            $table->string('contract_type', 20)->nullable()->after('work_norm_id');
            $table->date('contract_date')->nullable()->after('contract_type');
            $table->string('probation_unit', 10)->nullable()->after('leave_work_date');
            $table->unsignedSmallInteger('probation_amount')->nullable()->after('probation_unit');
            $table->string('workplace_type', 20)->nullable()->after('probation_amount');
            $table->string('working_time_type', 20)->nullable()->after('workplace_type');
            $table->string('work_schedule', 20)->nullable()->after('working_time_type');
            $table->json('work_hours')->nullable()->after('work_schedule');
            $table->json('rest_days')->nullable()->after('work_hours');
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table): void {
            $table->dropColumn([
                'contract_type',
                'contract_date',
                'probation_unit',
                'probation_amount',
                'workplace_type',
                'working_time_type',
                'work_schedule',
                'work_hours',
                'rest_days',
            ]);
        });
    }
};
