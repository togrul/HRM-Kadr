<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_labor_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('personnel_labor_activities', 'structure_id')) {
                $table->unsignedBigInteger('structure_id')->nullable()->after('company_name');
                $table->foreign('structure_id')
                    ->references('id')
                    ->on('structures')
                    ->nullOnDelete();
                $table->index('structure_id');
            }

            if (! Schema::hasColumn('personnel_labor_activities', 'position_id')) {
                $table->integer('position_id')->nullable()->after('position');
                $table->foreign('position_id')
                    ->references('id')
                    ->on('positions')
                    ->nullOnDelete();
                $table->index('position_id');
            }
        });

        DB::table('personnel_labor_activities')
            ->where('is_current', true)
            ->whereNull('leave_date')
            ->where(function ($query) {
                $query->whereNull('structure_id')
                    ->orWhereNull('position_id');
            })
            ->orderBy('id')
            ->get(['id', 'tabel_no'])
            ->each(function ($labor): void {
                $personnel = DB::table('personnels')
                    ->where('tabel_no', $labor->tabel_no)
                    ->first(['structure_id', 'position_id']);

                if (! $personnel) {
                    return;
                }

                DB::table('personnel_labor_activities')
                    ->where('id', $labor->id)
                    ->update([
                        'structure_id' => $personnel->structure_id,
                        'position_id' => $personnel->position_id,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('personnel_labor_activities', function (Blueprint $table) {
            if (Schema::hasColumn('personnel_labor_activities', 'position_id')) {
                $table->dropForeign(['position_id']);
                $table->dropIndex(['position_id']);
                $table->dropColumn('position_id');
            }

            if (Schema::hasColumn('personnel_labor_activities', 'structure_id')) {
                $table->dropForeign(['structure_id']);
                $table->dropIndex(['structure_id']);
                $table->dropColumn('structure_id');
            }
        });
    }
};
