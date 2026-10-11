<?php

namespace App\Modules\Benefits\Livewire;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\BenefitRenewalWindow;
use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use App\Support\Livewire\ScopesPersonnelByStructure;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * İmtiyazlar iş sahəsinin qabığı: tab naviqasiyası və xülasə sayğacları. Hər tab Livewire\Tabs
 * altında ayrıca komponentdir. Modul şirkət növünə görə və ya admin tərəfindən söndürülübsə
 * səhifə 404 qaytarır.
 */
class Dashboard extends Component
{
    use InteractsWithTabbedWorkspace;
    use ScopesPersonnelByStructure;

    public string $activeTab = 'plans';

    public function mount(BenefitsAvailability $availability): void
    {
        abort_unless($availability->enabled(), 404);
        abort_unless($this->canView(), 403);
        $this->bootActiveTabFromRequest();
    }

    protected function allowedTabs(): array
    {
        $tabs = ['plans', 'enrollments', 'renewals'];

        if ($this->canViewCosts()) {
            $tabs[] = 'costs';
        }

        return $tabs;
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function allowedTabsList(): array
    {
        return $this->allowedTabs();
    }

    public function canView(): bool
    {
        return auth()->user()?->can('show-benefits') ?? false;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can('manage-benefits') ?? false;
    }

    public function canViewCosts(): bool
    {
        return auth()->user()?->can('show-benefit-costs') ?? false;
    }

    #[On('benefits-updated')]
    public function refreshSummary(): void
    {
        unset($this->summaryStats);
    }

    /**
     * @return array<int, array{key: string, value: int|float, accent: string}>
     */
    #[Computed]
    public function summaryStats(): array
    {
        $today = CarbonImmutable::today()->toDateString();
        $inForce = $this->scopeByPersonnel(BenefitEnrollment::query())
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $today);

        return [
            ['key' => 'plans', 'value' => BenefitPlan::query()->where('is_active', true)->count(), 'accent' => 'bg-sky-500'],
            ['key' => 'enrollments', 'value' => (clone $inForce)->count(), 'accent' => 'bg-emerald-500'],
            ['key' => 'open_windows', 'value' => BenefitRenewalWindow::query()->where('status', BenefitRenewalWindow::STATUS_OPEN)->count(), 'accent' => 'bg-amber-400'],
        ];
    }

    public function render(): View
    {
        return view('benefits::livewire.dashboard');
    }
}
