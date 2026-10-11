<?php

namespace App\Modules\Benefits\Livewire\Tabs;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\BenefitRenewalWindow;
use App\Modules\Benefits\Application\Services\BenefitRenewalService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * İllik yenilənmə: açıq/bağlı seçim pəncərələri və qərarların sayı; müqaviləsi yaxında bitən
 * paketlər; HR pəncərəni vaxtından əvvəl aça və ya bağlaya bilər.
 */
class RenewalsTab extends BenefitsTab
{
    protected function viewName(): string
    {
        return 'renewals';
    }

    /**
     * @return Collection<int, BenefitRenewalWindow>
     */
    #[Computed]
    public function windows(): Collection
    {
        $windows = BenefitRenewalWindow::query()
            ->with('plan:id,name,type,auto_renew')
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('closes_on')
            ->limit(50)
            ->get();

        $counts = $this->scopeByPersonnel(BenefitEnrollment::query())
            ->whereIn('renewal_window_id', $windows->pluck('id')->all() ?: [0])
            ->selectRaw("renewal_window_id, coalesce(renewal_decision, 'pending') as decision, count(*) as aggregate")
            ->groupBy('renewal_window_id', 'decision')
            ->get()
            ->groupBy('renewal_window_id');

        return $windows->each(function (BenefitRenewalWindow $window) use ($counts): void {
            $window->setAttribute('decision_counts', ($counts->get($window->id) ?? collect())
                ->mapWithKeys(fn ($row): array => [(string) $row->getAttribute('decision') => (int) $row->getAttribute('aggregate')])
                ->all());
        });
    }

    /**
     * Müqaviləsi 90 gün ərzində bitən və pəncərəsi hələ açılmamış paketlər.
     *
     * @return Collection<int, BenefitPlan>
     */
    #[Computed]
    public function upcomingPlans(): Collection
    {
        $today = CarbonImmutable::today();

        return BenefitPlan::query()
            ->where('is_active', true)
            ->whereNotNull('contract_end')
            ->whereDate('contract_end', '>=', $today->toDateString())
            ->whereDate('contract_end', '<=', $today->addDays(90)->toDateString())
            ->whereDoesntHave('renewalWindows', fn ($query) => $query->whereColumn('benefit_renewal_windows.contract_end', 'benefit_plans.contract_end'))
            ->orderBy('contract_end')
            ->get();
    }

    public function openWindow(int $planId, BenefitRenewalService $service): void
    {
        $this->guardManage();
        $service->openWindow(BenefitPlan::query()->findOrFail($planId));
        unset($this->windows, $this->upcomingPlans);
        $this->announce('window_opened');
    }

    public function closeWindow(int $windowId, BenefitRenewalService $service): void
    {
        $this->guardManage();
        $window = BenefitRenewalWindow::query()->where('status', BenefitRenewalWindow::STATUS_OPEN)->findOrFail($windowId);
        $service->close($window);
        unset($this->windows, $this->upcomingPlans);
        $this->announce('window_closed');
    }
}
