<?php

namespace App\Modules\Benefits\Console\Commands;

use App\Models\BenefitEnrollment;
use App\Modules\Benefits\Application\Services\BenefitLifecycleService;
use App\Modules\Benefits\Application\Services\BenefitPayrollSync;
use App\Modules\Benefits\Application\Services\BenefitRenewalService;
use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Gündəlik iş: bitmə tarixi keçmiş qoşulmaları bağlayır, xitam tarixi qoyulmuş əməkdaşların
 * qoşulmalarını bitirir, yenilənmə pəncərələrini açıb-bağlayır (bildirişlə) və qüvvədə olan
 * qoşulmaların Payroll girişlərini (işçi payı sətri, cari ayın natura gəliri) sinxronlaşdırır.
 */
class BenefitsDailyCommand extends Command
{
    protected $signature = 'benefits:daily {--date= : Y-m-d formatında «bu gün» (test/geri yükləmə üçün)}';

    protected $description = 'İmtiyaz qoşulmalarının gündəlik keçidləri, yenilənmə pəncərələri və payroll sinxronu.';

    public function handle(
        BenefitsAvailability $availability,
        BenefitLifecycleService $lifecycle,
        BenefitRenewalService $renewals,
        BenefitPayrollSync $payroll,
    ): int {
        $today = $this->option('date') ? CarbonImmutable::parse((string) $this->option('date'))->startOfDay() : CarbonImmutable::today();

        // Xitam və tarix keçidləri modul söndürülsə də aparılır — məlumat düzgün qalmalıdır.
        $transitions = $lifecycle->applyDueTransitions($today);

        $opened = 0;
        $closed = 0;

        if ($availability->enabled()) {
            $closed = $renewals->closeDueWindows($today);
            $opened = $renewals->openDueWindows($today);
        }

        $synced = 0;
        BenefitEnrollment::query()
            ->where(fn ($query) => $query->where('status', BenefitEnrollment::STATUS_ACTIVE)
                ->orWhereDate('end_date', '>=', $today->startOfMonth()->toDateString())
                ->orWhereDate('updated_at', '>=', $today->subDays(2)->toDateString()))
            ->distinct()
            ->pluck('tabel_no')
            ->merge($transitions['tabel_nos'])
            ->unique()
            ->each(function (string $tabelNo) use ($payroll, $today, &$synced): void {
                $payroll->syncEmployee($tabelNo, $today);
                $synced++;
            });

        $this->info(sprintf(
            'ended=%d terminated=%d windows_opened=%d windows_closed=%d synced=%d',
            $transitions['ended'],
            $transitions['terminated'],
            $opened,
            $closed,
            $synced,
        ));

        return self::SUCCESS;
    }
}
