<?php

namespace App\Modules\Personnel\Livewire\ProfessionalPortfolio;

use App\Models\Personnel;
use App\Modules\Personnel\Exports\ProfessionalPortfolioAnalyticsExport;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioAnalyticsService;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class AnalyticsPanel extends Component
{
    public int $personnelId;

    public string $statusFilter = 'verified';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(int $personnelId): void
    {
        abort_unless(ProfessionalPortfolioPermissionMatrix::canViewAnalytics(auth()->user()), 403);

        $this->personnelId = $personnelId;
    }

    public function placeholder()
    {
        return view('personnel::livewire.personnel.placeholders.professional-portfolio-tab');
    }

    #[Computed]
    public function analytics(): array
    {
        return app(ProfessionalPortfolioAnalyticsService::class)->build(
            Personnel::query()->select(['id'])->findOrFail($this->personnelId),
            [
                'status' => $this->statusFilter,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ],
        );
    }

    public function exportExcel()
    {
        abort_unless(ProfessionalPortfolioPermissionMatrix::canViewAnalytics(auth()->user()), 403);

        return Excel::download(
            new ProfessionalPortfolioAnalyticsExport($this->personnel(), $this->analytics),
            "professional-portfolio-analytics-{$this->personnelId}.xlsx"
        );
    }

    public function exportCsv()
    {
        abort_unless(ProfessionalPortfolioPermissionMatrix::canViewAnalytics(auth()->user()), 403);

        return Excel::download(
            new ProfessionalPortfolioAnalyticsExport($this->personnel(), $this->analytics),
            "professional-portfolio-analytics-{$this->personnelId}.csv",
            ExcelWriter::CSV
        );
    }

    protected function personnel(): Personnel
    {
        return Personnel::query()->select(['id', 'surname', 'name', 'patronymic'])->findOrFail($this->personnelId);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.professional-portfolio.analytics-panel');
    }
}
