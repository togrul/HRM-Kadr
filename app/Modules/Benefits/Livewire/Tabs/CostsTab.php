<?php

namespace App\Modules\Benefits\Livewire\Tabs;

use App\Models\BenefitPlan;
use App\Modules\Benefits\Application\Services\BenefitCostReportService;
use App\Services\StructureService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use stdClass;

/**
 * İmtiyaz xərcləri: seçilmiş ay üzrə işəgötürən xərci və işçi payı — paket və struktur üzrə.
 * Rəqəmlər yalnız istifadəçinin görünən strukturlarındakı əməkdaşlardan yığılır.
 */
class CostsTab extends BenefitsTab
{
    public string $month = '';

    public string $planFilter = '';

    public string $structureFilter = '';

    public function mount(): void
    {
        parent::mount();
        abort_unless($this->canViewCosts(), 403);
        $this->month = CarbonImmutable::today()->format('Y-m');
    }

    public function canViewCosts(): bool
    {
        return auth()->user()?->can('show-benefit-costs') ?? false;
    }

    protected function viewName(): string
    {
        return 'costs';
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function report(): array
    {
        abort_unless($this->canViewCosts(), 403);

        return app(BenefitCostReportService::class)->report(app(StructureService::class)->scopeFor(), [
            'month' => $this->month,
            'plan_id' => (int) $this->planFilter ?: null,
            'structure_id' => (int) $this->structureFilter ?: null,
        ]);
    }

    /**
     * @return Collection<int, BenefitPlan>
     */
    #[Computed]
    public function planOptions(): Collection
    {
        return BenefitPlan::query()->orderBy('sort')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Süzgəc üçün yalnız istifadəçinin görə bildiyi strukturlar.
     *
     * @return Collection<int, stdClass>
     */
    #[Computed]
    public function structureOptions(): Collection
    {
        $query = DB::table('structures')->orderBy('code')->orderBy('name');
        app(StructureService::class)->scopeFor()->constrain($query, 'id');

        return $query->get(['id', 'name']);
    }
}
