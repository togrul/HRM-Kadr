<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gəlir vergisi güzəştləri və azadolmaları — effektiv tarixli, rejim üzrə (docs/payroll-legal-basis.md §5):
 *
 *  - `fixed_amount`: vergi tutulan gəlirdən sabit məbləğ çıxılır. Seed: Vergi Məcəlləsi m.102.1.6 —
 *    əsas iş yerində aylıq gəlir 2500 manatadək olduqda 200 manat azaddır (bütün rejimlər).
 *  - `taxable_components`: vergi bazasına yalnız sadalanan komponent kodları daxil olur. Seed: VM
 *    m.102.1.14.15 — hərbi qulluqçulara ödənilən bütün ödənişlər, vəzifə və hərbi rütbəyə görə
 *    maaşlar istisna olmaqla, azaddır (yalnız «military» rejimi; kodlar `base`, `rank_supplement`).
 *
 * Seed sətirləri artıq kilidlənmiş (ödənilmiş) ayları dəyişmir: effective_from ən son kilidlənmiş adi
 * hesablamanın ayından sonrakı ayın 1-i götürülür (kilidli ay yoxdursa — qanuni başlanğıc tarix).
 * Retro mühərriki kilidli payslipləri cari qaydalarla yenidən hesabladığı üçün əks halda keçmiş aylar
 * üzrə avtomatik «geri ödəniş» yaranardı. Keçmiş aylar üzrə düzəliş (vergi bəyannaməsi) mühasibin işidir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('statutory_exemptions')) {
            Schema::create('statutory_exemptions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('regime_id')->nullable()->constrained('compensation_regimes')->nullOnDelete();
                $table->string('code', 40);
                $table->string('legal_basis', 60);
                $table->string('kind', 30)->default('fixed_amount'); // fixed_amount | taxable_components
                $table->string('component_code', 30)->default('income_tax');
                $table->decimal('amount', 12, 2)->nullable();
                $table->decimal('income_up_to', 12, 2)->nullable();
                $table->boolean('main_workplace_only')->default(false);
                $table->json('component_codes')->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['component_code', 'effective_from'], 'stat_exempt_component_from_idx');
            });
        }

        $this->seed('vm_102_1_6', null, [
            'legal_basis' => 'VM 102.1.6',
            'kind' => 'fixed_amount',
            'amount' => 200,
            'income_up_to' => 2500,
            'main_workplace_only' => true,
        ], '2019-01-01');

        $militaryId = Schema::hasTable('compensation_regimes')
            ? DB::table('compensation_regimes')->where('code', 'military')->value('id')
            : null;

        if ($militaryId !== null) {
            $this->seed('vm_102_1_14_15', (int) $militaryId, [
                'legal_basis' => 'VM 102.1.14.15',
                'kind' => 'taxable_components',
                'component_codes' => json_encode(['base', 'rank_supplement']),
            ], '2026-01-01');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_exemptions');
    }

    /**
     * Sətri yalnız eyni kodlu sətir yoxdursa yazır — mühasibin dəyişdiyi və ya sildiyi sətir
     * yenidən yaradılmır (təkrar `migrate` dublikat yaratmır; MySQL-də upsert adi insert-ə düşür).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function seed(string $code, ?int $regimeId, array $attributes, string $lawFrom): void
    {
        if (DB::table('statutory_exemptions')->where('code', $code)->exists()) {
            return;
        }

        $now = now();

        DB::table('statutory_exemptions')->insert($attributes + [
            'regime_id' => $regimeId,
            'code' => $code,
            'component_code' => 'income_tax',
            'effective_from' => $this->safeStart($lawFrom),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** Qanuni başlanğıc tarix, amma ən son kilidlənmiş adi hesablamanın ayından sonra. */
    private function safeStart(string $lawFrom): string
    {
        if (! Schema::hasTable('payroll_runs') || ! Schema::hasTable('payroll_periods')) {
            return $lawFrom;
        }

        $lastLocked = DB::table('payroll_runs')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->where('payroll_runs.status', 'locked')
            ->where('payroll_runs.run_type', 'regular')
            ->max('payroll_periods.ends_on');

        if ($lastLocked === null) {
            return $lawFrom;
        }

        $next = Carbon::parse((string) $lastLocked)->addDay()->startOfMonth()->toDateString();

        return max($lawFrom, $next);
    }
};
