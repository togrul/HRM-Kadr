<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\TrainingNeeds\Application\Services\TrainingExecutiveReportingService;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedCoverageService;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class Reports extends Component
{
    use InteractsWithTrainingNeedsAccess;
    use WithRuntimeMemo;

    public ?int $reportYear = null;

    public ?int $reportQuarter = null;

    public array $reportYearOptions = [];

    public function mount(): void
    {
        $this->authorizeTrainingNeedsView();
        $years = $this->loadAvailableYears();
        $defaultYear = $years[0];
        $requestedYear = request()->filled('report_year')
            ? (int) request()->integer('report_year', $defaultYear)
            : $defaultYear;

        $this->reportYearOptions = collect($years)
            ->map(fn (int $year): array => ['id' => $year, 'label' => (string) $year])
            ->all();
        $this->reportYear = in_array($requestedYear, $years, true) ? $requestedYear : $defaultYear;
        $this->reportQuarter = request()->integer('report_quarter') ?: null;
    }

    public function updatedReportYear($value): void
    {
        $years = collect($this->reportYearOptions)
            ->pluck('id')
            ->filter(fn ($year) => is_numeric($year))
            ->map(fn ($year) => (int) $year)
            ->values()
            ->all();

        $fallbackYear = $years[0] ?? (int) now()->year;
        $normalized = filled($value) ? (int) $value : $fallbackYear;
        $this->reportYear = in_array($normalized, $years, true) ? $normalized : $fallbackYear;
        $this->resetRuntimeMemo();
    }

    public function updatedReportQuarter(): void
    {
        $this->resetRuntimeMemo();
    }

    public function getExecutiveSummaryProperty(): array
    {
        return $this->rememberRuntime(
            'trainingNeedsReports.executiveSummary.'.$this->reportYear.'.'.($this->reportQuarter ?: 'all'),
            fn (): array => app(TrainingExecutiveReportingService::class)->executiveSummary($this->reportYear, $this->reportQuarter)
        );
    }

    public function getAnnualReportRowsProperty()
    {
        return $this->rememberRuntime('trainingNeedsReports.annualRows', fn () => app(TrainingExecutiveReportingService::class)->annualRows());
    }

    public function getQuarterlyReportRowsProperty()
    {
        return $this->rememberRuntime(
            'trainingNeedsReports.quarterlyRows.'.$this->reportYear,
            fn () => app(TrainingExecutiveReportingService::class)->quarterlyRows($this->reportYear)
        );
    }

    public function getEmployeeHoursRowsProperty()
    {
        return $this->rememberRuntime(
            'trainingNeedsReports.employeeHours.'.$this->reportYear.'.'.($this->reportQuarter ?: 'all'),
            fn () => app(TrainingExecutiveReportingService::class)->employeeHoursRows($this->reportYear, $this->reportQuarter)
        );
    }

    public function getDeliveryTypeRowsProperty()
    {
        return $this->rememberRuntime(
            'trainingNeedsReports.deliveryTypes.'.$this->reportYear.'.'.($this->reportQuarter ?: 'all'),
            fn () => app(TrainingExecutiveReportingService::class)->deliveryTypeRows($this->reportYear, $this->reportQuarter)
        );
    }

    public function getOutcomeRowsProperty()
    {
        return $this->rememberRuntime(
            'trainingNeedsReports.outcomes.'.$this->reportYear.'.'.($this->reportQuarter ?: 'all'),
            fn () => app(TrainingExecutiveReportingService::class)->outcomeRows($this->reportYear, $this->reportQuarter)
        );
    }

    public function getCoverageSummaryProperty(): array
    {
        return $this->rememberRuntime(
            'trainingNeedsReports.coverageSummary.'.$this->reportYear.'.'.($this->reportQuarter ?: 'all'),
            fn (): array => app(TrainingNeedCoverageService::class)->summary($this->reportYear, $this->reportQuarter)
        );
    }

    public function getCoverageCompetencyRowsProperty()
    {
        return $this->rememberRuntime(
            'trainingNeedsReports.coverageCompetencies.'.$this->reportYear.'.'.($this->reportQuarter ?: 'all'),
            fn () => app(TrainingNeedCoverageService::class)->competencyRows($this->reportYear, $this->reportQuarter)
        );
    }

    public function getCoverageProgramRowsProperty()
    {
        return $this->rememberRuntime(
            'trainingNeedsReports.coveragePrograms.'.$this->reportYear.'.'.($this->reportQuarter ?: 'all'),
            fn () => app(TrainingNeedCoverageService::class)->programRows($this->reportYear, $this->reportQuarter)
        );
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.reports');
    }

    private function loadAvailableYears(): array
    {
        $years = app(TrainingExecutiveReportingService::class)->availableYears();

        return $years === [] ? [(int) now()->year] : $years;
    }
}
