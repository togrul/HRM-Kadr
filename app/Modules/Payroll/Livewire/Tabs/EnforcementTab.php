<?php

namespace App\Modules\Payroll\Livewire\Tabs;

use App\Models\EnforcementDeduction;
use App\Models\EnforcementDocument;
use App\Models\PayrollRun;
use App\Modules\Payroll\Application\Services\EnforcementService;
use App\Modules\Payroll\Application\Services\PayrollRunReadService;
use App\Support\Livewire\DownloadsReportsTable;
use App\Support\Livewire\SearchesPersonnel;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * İcra sənədləri reyestri (işçi üzrə) və icra məmuruna göndəriləcək tutulma siyahısı (run üzrə).
 *
 * @property-read Collection<int, EnforcementDocument> $documents
 * @property-read Collection<int, PayrollRun> $lockedRuns
 * @property-read list<array<string, mixed>> $reportRows
 */
class EnforcementTab extends PayrollTab
{
    use DownloadsReportsTable;
    use SearchesPersonnel;

    #[Locked]
    public ?int $periodId = null;

    public bool $formOpen = false;

    public array $documentForm = [];

    public ?int $reportRunId = null;

    #[Locked]
    public ?int $statusDocumentId = null;

    public string $statusTarget = 'closed';

    public string $statusReason = '';

    public function mount(?int $periodId = null): void
    {
        parent::mount();
        $this->periodId = $periodId;
        $this->resetForm();
        $this->reportRunId = $this->lockedRuns->first()?->id;
    }

    protected function viewName(): string
    {
        return 'enforcement';
    }

    #[Computed]
    public function documents(): Collection
    {
        if (! $this->selectedTabelNo || ! $this->tabelInScope($this->selectedTabelNo)) {
            return collect();
        }

        return EnforcementDocument::query()
            ->where('tabel_no', $this->selectedTabelNo)
            ->with(['deductions' => fn ($q) => $q->with('run.period')->latest('paid_on')])
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderBy('priority')
            ->orderBy('received_on')
            ->get();
    }

    /** Hesabat üçün kilidlənmiş run-lar (seçilmiş dövr, yoxdursa son 12). */
    #[Computed]
    public function lockedRuns(): Collection
    {
        return app(PayrollRunReadService::class)->visibleQuery(auth()->user())
            ->where('status', 'locked')
            ->when($this->periodId, fn ($q) => $q->where('payroll_period_id', $this->periodId))
            ->with('period')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function reportRows(): array
    {
        $run = $this->reportRun();

        return $run ? $this->scopeRows(app(EnforcementService::class)->reportRows($run)) : [];
    }

    public function openForm(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetForm();
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->resetValidation();
    }

    public function saveDocument(EnforcementService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->selectedTabelNo !== null && $this->tabelInScope($this->selectedTabelNo), 403);

        $data = $this->validate([
            'documentForm.kind' => 'required|in:'.implode(',', EnforcementDocument::KINDS),
            'documentForm.priority' => 'nullable|integer|min:1|max:5',
            'documentForm.issuer_type' => 'required|in:'.implode(',', EnforcementDocument::ISSUER_TYPES),
            'documentForm.issuer_name' => 'required|string|max:190',
            'documentForm.officer_name' => 'nullable|string|max:190',
            'documentForm.document_no' => 'required|string|max:80',
            'documentForm.document_date' => 'nullable|date',
            'documentForm.received_on' => 'required|date',
            'documentForm.method' => 'required|in:'.implode(',', EnforcementDocument::METHODS),
            'documentForm.fixed_amount' => 'nullable|numeric|min:0.01|required_if:documentForm.method,fixed',
            'documentForm.percent' => 'nullable|numeric|min:0.01|max:70|required_if:documentForm.method,percent',
            'documentForm.recipient_name' => 'required|string|max:190',
            'documentForm.recipient_account' => 'nullable|string|max:64',
            'documentForm.recipient_details' => 'nullable|string|max:255',
            'documentForm.total_debt' => 'nullable|numeric|min:0.01',
            'documentForm.starts_on' => 'required|date',
            'documentForm.ends_on' => 'nullable|date|after_or_equal:documentForm.starts_on',
            'documentForm.note' => 'nullable|string|max:2000',
        ], attributes: collect(array_keys($this->documentForm))
            ->mapWithKeys(fn (string $key): array => ['documentForm.'.$key => __('payroll::enforcement.fields.'.$key)])
            ->all())['documentForm'];

        $service->create($this->selectedTabelNo, $data);

        $this->formOpen = false;
        $this->resetForm();
        unset($this->documents);
        $this->announce('saved');
    }

    public function startStatus(int $documentId, string $target): void
    {
        abort_unless($this->canManage(), 403);
        $this->findDocument($documentId);
        $this->statusDocumentId = $documentId;
        $this->statusTarget = in_array($target, EnforcementDocument::STATUSES, true) ? $target : 'closed';
        $this->statusReason = '';
    }

    public function saveStatus(EnforcementService $service): void
    {
        abort_unless($this->canManage(), 403);
        $this->validate(['statusReason' => 'required|string|max:500'], attributes: ['statusReason' => __('payroll::enforcement.status_reason')]);

        try {
            $service->setStatus($this->findDocument((int) $this->statusDocumentId), $this->statusTarget, $this->statusReason);
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());

            return;
        }

        $this->statusDocumentId = null;
        unset($this->documents);
        $this->announce('saved');
    }

    public function deleteDocument(int $documentId, EnforcementService $service): void
    {
        abort_unless($this->canManage(), 403);

        if (! $this->attempt(fn () => $service->delete($this->findDocument($documentId)))) {
            return;
        }

        unset($this->documents);
        $this->announce('deleted');
    }

    public function exportReport(): BinaryFileResponse
    {
        abort_unless($this->canExport() && $this->canViewAmounts(), 403);
        $run = $this->reportRun();
        abort_unless($run !== null, 404);

        $columns = collect(['tabel_no', 'full_name', 'pin', 'kind', 'issuer', 'document_no', 'document_date', 'recipient', 'recipient_account', 'wage_due', 'requested', 'amount', 'remaining', 'paid_on'])
            ->map(fn (string $key): array => ['key' => $key, 'label' => __('payroll::enforcement.report.cols.'.$key)])
            ->all();

        return $this->downloadReportTable($this->reportRows, $columns, 'icra-tutulmalari-'.($run->period->code ?? $run->id).'.xlsx');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function scopeRows(array $rows): array
    {
        return array_values(array_filter($rows, fn (array $row): bool => $this->tabelInScope((string) $row['tabel_no'])));
    }

    private function reportRun(): ?PayrollRun
    {
        return $this->reportRunId ? $this->lockedRuns->firstWhere('id', $this->reportRunId) : null;
    }

    private function findDocument(int $id): EnforcementDocument
    {
        abort_unless($this->selectedTabelNo !== null && $this->tabelInScope($this->selectedTabelNo), 403);

        return EnforcementDocument::query()->where('tabel_no', $this->selectedTabelNo)->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->documentForm = [
            'kind' => 'alimony', 'priority' => '', 'issuer_type' => 'enforcement_officer', 'issuer_name' => '', 'officer_name' => '',
            'document_no' => '', 'document_date' => '', 'received_on' => now()->toDateString(), 'method' => 'percent',
            'fixed_amount' => '', 'percent' => '25', 'recipient_name' => '', 'recipient_account' => '', 'recipient_details' => '',
            'total_debt' => '', 'starts_on' => now()->startOfMonth()->toDateString(), 'ends_on' => '', 'note' => '',
        ];
    }

    /** Sənədin bütün tutulmalarının cəmi. */
    public function deductedTotal(EnforcementDocument $document): float
    {
        return round((float) $document->deductions->sum(fn (EnforcementDeduction $d): float => (float) $d->amount), 2);
    }
}
