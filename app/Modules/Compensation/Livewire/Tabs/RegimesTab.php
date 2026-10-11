<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Modules\Compensation\Application\Services\CompanySectorSettings;
use App\Modules\Compensation\Application\Services\RegimeAlignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

/**
 * @property-read array<int, array{id: int, label: string}> $regimeOptions
 * @property-read list<array{tabel_no: string, full_name: string, compensation_id: int, regime_id: int|null, regime_name: string, effective_from: string|null, earliest_date: string}> $mismatches
 *
 * Şirkət sektoru (standart rejim) və rejimi standartdan fərqli olan işçilər — toplu düzəliş
 * təsdiqlə, effektiv tarixlə və audit jurnalı ilə (docs/payroll-legal-basis.md §8).
 */
class RegimesTab extends CompensationTab
{
    public string $sector = '';

    public string $sectorReason = '';

    /** @var list<string> */
    public array $selected = [];

    public string $alignDate = '';

    public string $alignReason = '';

    /** @var array<string, string> son əməliyyatda keçirilməyən işçilər: tabel_no → səbəb */
    public array $skipped = [];

    public function mount(): void
    {
        parent::mount();

        $this->sector = app(CompanySectorSettings::class)->sector();
        $this->alignDate = now()->addMonthNoOverflow()->startOfMonth()->toDateString();
    }

    protected function viewName(): string
    {
        return 'regimes';
    }

    #[Computed]
    public function sectorIsExplicit(): bool
    {
        return app(CompanySectorSettings::class)->isExplicit();
    }

    #[Computed]
    public function defaultRegimeName(): ?string
    {
        $id = app(CompanySectorSettings::class)->defaultRegimeId();

        foreach ($this->regimeOptions as $option) {
            if ($option['id'] === $id) {
                return $option['label'];
            }
        }

        return null;
    }

    /**
     * @return list<array{tabel_no: string, full_name: string, compensation_id: int, regime_id: int|null, regime_name: string, effective_from: string|null, earliest_date: string}>
     */
    #[Computed]
    public function mismatches(): array
    {
        return app(RegimeAlignmentService::class)->mismatches($this->personnelScope());
    }

    public function saveSector(CompanySectorSettings $settings): void
    {
        $this->guardManage();

        $this->validate([
            'sector' => 'required|in:'.implode(',', CompanySectorSettings::sectors()),
            'sectorReason' => 'required|string|max:500',
        ], attributes: [
            'sector' => __('compensation::regimes.sector'),
            'sectorReason' => __('compensation::regimes.reason'),
        ]);

        $settings->setSector($this->sector, $this->sectorReason);

        $this->sectorReason = '';
        $this->selected = [];
        unset($this->mismatches, $this->defaultRegimeName, $this->sectorIsExplicit);
        $this->announce('saved');
    }

    public function toggleAll(): void
    {
        $all = array_column($this->mismatches, 'tabel_no');
        $this->selected = count($this->selected) === count($all) ? [] : $all;
    }

    /**
     * Seçilmiş işçilərin rejimini standarta keçirir (təsdiq modalı ilə çağırılır).
     */
    public function realign(RegimeAlignmentService $service): void
    {
        $this->guardManage();

        $this->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'string',
            'alignDate' => 'required|date',
            'alignReason' => 'required|string|max:500',
        ], attributes: [
            'selected' => __('compensation::regimes.employees'),
            'alignDate' => __('compensation::regimes.effective_from'),
            'alignReason' => __('compensation::regimes.reason'),
        ]);

        // Yalnız siyahıda görünən (struktur görünürlüyündə olan) işçilər.
        $visible = array_column($this->mismatches, 'tabel_no');
        $tabelNos = array_values(array_intersect(array_map('strval', $this->selected), $visible));

        try {
            $result = $service->realign($tabelNos, $this->alignDate, $this->alignReason);
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());

            return;
        }

        $this->skipped = $result['skipped'];
        $this->selected = [];
        $this->alignReason = '';
        unset($this->mismatches);

        $this->dispatch('notify', type: $result['done'] === [] ? 'error' : 'success', message: __('compensation::regimes.messages.realigned', [
            'done' => count($result['done']),
            'skipped' => count($result['skipped']),
        ]));
        $this->dispatch('compensation-updated');
    }

    public function render(): View
    {
        return parent::render()->with([
            'sectors' => collect(CompanySectorSettings::sectors())
                ->map(fn (string $sector): array => ['id' => $sector, 'label' => __('compensation::regimes.sectors.'.$sector)])
                ->all(),
        ]);
    }
}
