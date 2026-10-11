<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Models\EmployeeTaxRelief;
use App\Models\StatutoryExemption;
use App\Modules\Compensation\Application\Services\TaxReliefService;
use App\Support\Uploads\UploadRules;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\WithFileUploads;

/**
 * Seçilmiş işçinin gəlir vergisi güzəşti kartları (Vergi Məcəlləsi m.102.1-1, 102.2–102.5, 102.7, 102.8).
 */
class ReliefsTab extends CompensationTab
{
    use WithFileUploads;

    #[Reactive]
    public ?string $tabelNo = null;

    public array $reliefForm = [
        'category' => 'vm_102_3',
        'basis_detail' => '',
        'document_no' => '',
        'document_date' => '',
        'document_issuer' => '',
        'status_from' => '',
        'submitted_on' => '',
        'valid_to' => '',
        'note' => '',
    ];

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $document = null;

    #[Locked]
    public ?int $endingId = null;

    public string $endDate = '';

    public string $endReason = '';

    protected function viewName(): string
    {
        return 'reliefs';
    }

    protected function panels(): array
    {
        return ['relief', 'end'];
    }

    protected function resetPanel(string $panel): void
    {
        $this->reliefForm = ['category' => 'vm_102_3', 'basis_detail' => '', 'document_no' => '', 'document_date' => '', 'document_issuer' => '', 'status_from' => '', 'submitted_on' => now()->toDateString(), 'valid_to' => '', 'note' => ''];
        $this->document = null;
        $this->endDate = '';
        $this->endReason = '';
        $this->resetValidation();
    }

    #[Computed]
    public function reliefs(): Collection
    {
        $tabelNo = $this->visibleTabel();

        if (! $tabelNo) {
            return collect();
        }

        return EmployeeTaxRelief::query()
            ->where('tabel_no', $tabelNo)
            ->orderByDesc('payroll_from')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Kateqoriya → bu gün qüvvədə olan məbləğ (`statutory_exemptions`, növ `personal`).
     *
     * @return array<string, float>
     */
    #[Computed]
    public function amounts(): array
    {
        $today = now()->toDateString();

        return StatutoryExemption::query()
            ->where('kind', StatutoryExemption::KIND_PERSONAL)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $today)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today))
            ->orderBy('effective_from')
            ->get(['code', 'amount'])
            ->mapWithKeys(fn (StatutoryExemption $row): array => [$row->code => (float) $row->amount])
            ->all();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    #[Computed]
    public function categoryOptions(): array
    {
        return collect(EmployeeTaxRelief::CATEGORIES)
            ->map(fn (string $basis, string $code): array => ['id' => $code, 'label' => __('compensation::reliefs.categories.'.$code)])
            ->values()
            ->all();
    }

    public function saveRelief(TaxReliefService $service): void
    {
        $this->guardManage();
        abort_unless($this->visibleTabel() !== null, 403);

        $this->validate([
            'reliefForm.category' => 'required|in:'.implode(',', array_keys(EmployeeTaxRelief::CATEGORIES)),
            'reliefForm.basis_detail' => 'nullable|string|max:160',
            'reliefForm.document_no' => 'required|string|max:80',
            'reliefForm.document_date' => 'nullable|date',
            'reliefForm.document_issuer' => 'nullable|string|max:160',
            'reliefForm.status_from' => 'required|date',
            'reliefForm.submitted_on' => 'required|date|after_or_equal:reliefForm.status_from',
            'reliefForm.valid_to' => 'nullable|date|after_or_equal:reliefForm.status_from',
            'reliefForm.note' => 'nullable|string|max:2000',
            'document' => UploadRules::document(false),
        ], attributes: [
            'reliefForm.category' => __('compensation::reliefs.category'),
            'reliefForm.basis_detail' => __('compensation::reliefs.basis_detail'),
            'reliefForm.document_no' => __('compensation::reliefs.document_no'),
            'reliefForm.document_date' => __('compensation::reliefs.document_date'),
            'reliefForm.document_issuer' => __('compensation::reliefs.document_issuer'),
            'reliefForm.status_from' => __('compensation::reliefs.status_from'),
            'reliefForm.submitted_on' => __('compensation::reliefs.submitted_on'),
            'reliefForm.valid_to' => __('compensation::reliefs.valid_to'),
            'reliefForm.note' => __('compensation::dashboard.fields.note'),
            'document' => __('compensation::reliefs.document'),
        ]);

        $service->create((string) $this->tabelNo, $this->reliefForm, $this->document);

        $this->panel = '';
        $this->resetPanel('relief');
        unset($this->reliefs);
        $this->announce('saved');
    }

    public function startEnd(int $id): void
    {
        $this->guardManage();
        $this->findRelief($id);
        $this->resetPanel('end');
        $this->endingId = $id;
        $this->endDate = now()->toDateString();
        $this->panel = 'end';
    }

    public function saveEnd(TaxReliefService $service): void
    {
        $this->guardManage();

        $this->validate([
            'endDate' => 'required|date',
            'endReason' => 'required|string|max:500',
        ], attributes: [
            'endDate' => __('compensation::reliefs.valid_to'),
            'endReason' => __('compensation::reliefs.end_reason'),
        ]);

        $relief = $this->findRelief((int) $this->endingId);

        try {
            $service->end($relief, $this->endDate, $this->endReason);
        } catch (ValidationException $exception) {
            $this->addError('endDate', (string) collect($exception->errors())->flatten()->first());

            return;
        }

        $this->panel = '';
        $this->endingId = null;
        unset($this->reliefs);
        $this->announce('saved');
    }

    public function deleteRelief(int $id, TaxReliefService $service): void
    {
        $this->guardManage();

        try {
            $service->delete($this->findRelief($id));
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());

            return;
        }

        unset($this->reliefs);
        $this->announce('deleted');
    }

    private function findRelief(int $id): EmployeeTaxRelief
    {
        $tabelNo = $this->visibleTabel();
        abort_unless($tabelNo !== null, 403);

        return EmployeeTaxRelief::query()->where('tabel_no', $tabelNo)->findOrFail($id);
    }
}
