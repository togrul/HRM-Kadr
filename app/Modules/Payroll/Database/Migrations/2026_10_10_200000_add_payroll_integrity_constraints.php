<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Əmək haqqı bütövlüyü:
 *  - `payroll_runs.regular_slot`: dövr + rejim üzrə yalnız bir müntəzəm run (unikal indeks;
 *    NULL-lar — off-cycle run-lar — MySQL, PostgreSQL və SQLite-da bir-birinə mane olmur).
 *    Mövcud təkrarlarda yer ən irəli statuslu (locked → approved → calculated → draft),
 *    sonra ən köhnə run-a verilir; qalanlar əvvəlki kimi qalır, sadəcə yer tutmur.
 *  - `payslips.tabel_no`: əməkdaş silinəndə hesab vərəqələri artıq kaskadla silinmir
 *    (restrict) — ödənilmiş əmək haqqının izi itməməlidir.
 */
return new class extends Migration
{
    private const RANK = ['locked' => 0, 'approved' => 1, 'calculated' => 2, 'draft' => 3];

    public function up(): void
    {
        if (Schema::hasTable('payroll_runs') && ! Schema::hasColumn('payroll_runs', 'regular_slot')) {
            Schema::table('payroll_runs', function (Blueprint $table): void {
                $table->string('regular_slot', 40)->nullable()->after('run_type');
            });

            $taken = [];
            DB::table('payroll_runs')
                ->where('run_type', 'regular')
                ->orderBy('id')
                ->get(['id', 'payroll_period_id', 'regime_id', 'status'])
                ->sortBy(fn (object $run): array => [self::RANK[$run->status] ?? 9, (int) $run->id])
                ->each(function (object $run) use (&$taken): void {
                    $slot = $run->payroll_period_id.':'.($run->regime_id ?? 0);
                    if (isset($taken[$slot])) {
                        return;
                    }

                    $taken[$slot] = true;
                    DB::table('payroll_runs')->where('id', $run->id)->update(['regular_slot' => $slot]);
                });

            Schema::table('payroll_runs', function (Blueprint $table): void {
                $table->unique('regular_slot', 'payroll_runs_regular_slot_unique');
            });
        }

        if (Schema::hasTable('payslips')) {
            Schema::table('payslips', function (Blueprint $table): void {
                $table->dropForeign(['tabel_no']);
            });

            Schema::table('payslips', function (Blueprint $table): void {
                $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->restrictOnDelete()->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payslips')) {
            Schema::table('payslips', function (Blueprint $table): void {
                $table->dropForeign(['tabel_no']);
            });

            Schema::table('payslips', function (Blueprint $table): void {
                $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
            });
        }

        if (Schema::hasColumn('payroll_runs', 'regular_slot')) {
            Schema::table('payroll_runs', function (Blueprint $table): void {
                $table->dropUnique('payroll_runs_regular_slot_unique');
            });

            Schema::table('payroll_runs', function (Blueprint $table): void {
                $table->dropColumn('regular_slot');
            });
        }
    }
};
