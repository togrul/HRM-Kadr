<?php

namespace App\Modules\Payroll\Livewire\Tabs;

use App\Modules\Payroll\Application\Services\TaxAdjustmentReportService;
use App\Support\Livewire\DownloadsReportsTable;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @property-read list<array<string, mixed>> $rows
 * @property-read array<string, float> $totals
 *
 * «Vergi düzəliş hesabatı»: kilidlənmiş ayların yaddaşda cari qaydalarla yenidən hesablanması (yalnız
 * oxu), XLSX ixracı və — ayrıca, təsdiqlə — artıq tutulmuş məbləğin növbəti run-a qaytarılması.
 */
class TaxAdjustmentTab extends PayrollTab
{
    use DownloadsReportsTable;

    public string $fromMonth = '';

    public string $toMonth = '';

    /** Hesabat düyməsi basılanda hesablanır (ağır əməliyyatdır). */
    public bool $loaded = false;

    /** @var list<int> */
    public array $selected = [];

    /** @var list<string> */
    public array $refundComponents = ['income_tax_ee'];

    public ?int $payYear = null;

    public ?int $payMonth = null;

    public string $refundReason = '';

    public function mount(): void
    {
        parent::mount();
        abort_unless($this->canViewAmounts(), 403);

        $this->fromMonth = now()->startOfYear()->format('Y-m');
        $this->toMonth = now()->format('Y-m');
        $this->payYear = (int) now()->format('Y');
        $this->payMonth = (int) now()->format('n');
    }

    protected function viewName(): string
    {
        return 'tax-adjustment';
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function rows(): array
    {
        if (! $this->loaded) {
            return [];
        }

        return app(TaxAdjustmentReportService::class)->build($this->fromMonth, $this->toMonth, $this->personnelScope());
    }

    /**
     * @return array<string, float>
     */
    #[Computed]
    public function totals(): array
    {
        return TaxAdjustmentReportService::totals($this->rows);
    }

    public function generate(): void
    {
        $this->validateMonths();
        $this->loaded = true;
        $this->selected = [];
        unset($this->rows, $this->totals);
    }

    public function toggleAll(): void
    {
        $ids = array_map(fn (array $row): int => (int) $row['payslip_id'], $this->rows);
        $this->selected = count($this->selected) === count($ids) ? [] : $ids;
    }

    public function export(): BinaryFileResponse
    {
        abort_unless($this->canExport(), 403);
        $this->validateMonths();
        $this->loaded = true;

        $columns = [
            ['key' => 'period', 'label' => __('payroll::tax_adjustment.cols.period')],
            ['key' => 'tabel_no', 'label' => __('payroll::dashboard.export.cols.tabel_no')],
            ['key' => 'full_name', 'label' => __('payroll::dashboard.export.cols.full_name')],
            ['key' => 'run_type', 'label' => __('payroll::tax_adjustment.cols.run_type')],
            ['key' => 'paid_regime', 'label' => __('payroll::tax_adjustment.cols.paid_regime')],
            ['key' => 'target_regime', 'label' => __('payroll::tax_adjustment.cols.target_regime')],
            ['key' => 'reliefs', 'label' => __('payroll::tax_adjustment.cols.reliefs')],
        ];

        foreach (TaxAdjustmentReportService::COMPONENTS as $code) {
            foreach (['paid', 'now', 'diff'] as $part) {
                $columns[] = ['key' => $code.'_'.$part, 'label' => __('payroll::tax_adjustment.components.'.$code).' — '.__('payroll::tax_adjustment.parts.'.$part)];
            }
        }

        return $this->downloadReportTable(
            TaxAdjustmentReportService::exportRows($this->rows)->all(),
            $columns,
            'vergi-duzelis-'.$this->fromMonth.'-'.$this->toMonth.'.xlsx',
        );
    }

    /**
     * Seçilmiş sətirlər üzrə artıq tutulmuş məbləği növbəti run-a qaytarma kimi əlavə edir (təsdiq modalı ilə).
     */
    public function recordRefunds(TaxAdjustmentReportService $service): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer',
            'refundComponents' => 'required|array|min:1',
            'refundComponents.*' => 'in:'.implode(',', TaxAdjustmentReportService::EMPLOYEE_SIDE),
            'payYear' => 'required|integer|min:2000|max:2100',
            'payMonth' => 'required|integer|min:1|max:12',
            'refundReason' => 'required|string|max:500',
        ], attributes: [
            'selected' => __('payroll::tax_adjustment.selected'),
            'refundComponents' => __('payroll::tax_adjustment.refund_components'),
            'payYear' => __('payroll::dashboard.fields.year'),
            'payMonth' => __('payroll::dashboard.fields.month'),
            'refundReason' => __('payroll::tax_adjustment.reason'),
        ]);

        try {
            $result = $service->recordRefunds(array_map('intval', $this->selected), $this->refundComponents, (int) $this->payYear, (int) $this->payMonth, $this->refundReason, $this->personnelScope());
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());

            return;
        }

        $this->selected = [];
        $this->refundReason = '';
        unset($this->rows, $this->totals);

        $this->dispatch('notify', type: 'success', message: __('payroll::tax_adjustment.messages.recorded', [
            'count' => $result['recorded'],
            'total' => number_format($result['total'], 2, ',', ' '),
        ]));
        $this->dispatch('payroll-updated');
    }

    private function validateMonths(): void
    {
        $this->validate([
            'fromMonth' => 'required|date_format:Y-m',
            'toMonth' => 'required|date_format:Y-m|after_or_equal:fromMonth',
        ], attributes: [
            'fromMonth' => __('payroll::tax_adjustment.from'),
            'toMonth' => __('payroll::tax_adjustment.to'),
        ]);
    }
}
