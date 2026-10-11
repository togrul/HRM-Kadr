<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Models\StatutoryExemption;
use App\Models\StatutoryRate;
use App\Modules\Compensation\Application\Services\CompensationCatalogService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Statutory rate table (income tax, DSMF, unemployment, medical) with bracket ladders.
 */
class StatutoryTab extends CompensationTab
{
    public array $statutoryForm = [
        'regime_id' => null,
        'component_code' => 'income_tax',
        'payer' => 'ee',
        'base' => 'social',
        'effective_from' => '',
    ];

    public array $statutoryBrackets = [];

    /** Sabit məbləğli gəlir vergisi azadolması (VM m.102.1.6 kimi). */
    public array $exemptionForm = [
        'regime_id' => null,
        'code' => 'vm_102_1_6',
        'legal_basis' => 'VM 102.1.6',
        'amount' => '200',
        'income_up_to' => '2500',
        'main_workplace_only' => true,
        'effective_from' => '',
    ];

    protected function viewName(): string
    {
        return 'statutory';
    }

    protected function panels(): array
    {
        return ['statutory', 'exemption'];
    }

    protected function resetPanel(string $panel): void
    {
        $panel === 'exemption' ? $this->resetExemptionForm() : $this->resetStatutoryForm();
    }

    #[Computed]
    public function statutoryRates(): Collection
    {
        return StatutoryRate::query()
            ->with('regime:id,name')
            ->orderBy('component_code')
            ->orderBy('payer')
            ->get();
    }

    #[Computed]
    public function statutoryExemptions(): Collection
    {
        return StatutoryExemption::query()
            ->with('regime:id,name')
            ->orderBy('code')
            ->orderByDesc('effective_from')
            ->get();
    }

    public function saveStatutoryExemption(CompensationCatalogService $service): void
    {
        $this->guardManage();

        $data = $this->validate([
            'exemptionForm.regime_id' => 'nullable|exists:compensation_regimes,id',
            'exemptionForm.code' => 'required|string|max:40|regex:/^[a-z0-9_]+$/',
            'exemptionForm.legal_basis' => 'required|string|max:60',
            'exemptionForm.amount' => 'required|numeric|min:0.01|max:100000',
            'exemptionForm.income_up_to' => 'nullable|numeric|min:0.01',
            'exemptionForm.main_workplace_only' => 'boolean',
            'exemptionForm.effective_from' => 'required|date',
        ], attributes: [
            'exemptionForm.regime_id' => __('compensation::dashboard.fields.regime'),
            'exemptionForm.code' => __('compensation::dashboard.exemptions.code'),
            'exemptionForm.legal_basis' => __('compensation::dashboard.exemptions.legal_basis'),
            'exemptionForm.amount' => __('compensation::dashboard.exemptions.amount'),
            'exemptionForm.income_up_to' => __('compensation::dashboard.exemptions.income_up_to'),
            'exemptionForm.main_workplace_only' => __('compensation::dashboard.exemptions.main_workplace_only'),
            'exemptionForm.effective_from' => __('compensation::dashboard.fields.effective_from'),
        ])['exemptionForm'];

        $service->createStatutoryExemption($data);

        $this->resetExemptionForm();
        $this->announce('saved');
    }

    public function resetExemptionForm(): void
    {
        $this->exemptionForm = ['regime_id' => null, 'code' => 'vm_102_1_6', 'legal_basis' => 'VM 102.1.6', 'amount' => '200', 'income_up_to' => '2500', 'main_workplace_only' => true, 'effective_from' => ''];
        $this->panel = '';
        $this->resetValidation();
    }

    public function deleteStatutoryExemption(int $id, CompensationCatalogService $service): void
    {
        $this->guardManage();
        $service->deleteStatutoryExemption($id);
        $this->announce('deleted');
    }

    public function addStatutoryBracket(): void
    {
        $this->statutoryBrackets[] = ['up_to' => '', 'rate' => ''];
    }

    public function removeStatutoryBracket(int $index): void
    {
        unset($this->statutoryBrackets[$index]);
        $this->statutoryBrackets = array_values($this->statutoryBrackets);
    }

    public function saveStatutoryRate(CompensationCatalogService $service): void
    {
        $this->guardManage();

        $data = $this->validate([
            'statutoryForm.regime_id' => 'nullable|exists:compensation_regimes,id',
            'statutoryForm.component_code' => 'required|in:income_tax,dsmf,unemployment,medical',
            'statutoryForm.payer' => 'required|in:ee,er',
            'statutoryForm.base' => 'required|in:taxable,social',
            'statutoryForm.effective_from' => 'required|date',
            'statutoryBrackets' => 'required|array|min:1',
            'statutoryBrackets.*.rate' => 'required|numeric|min:0|max:100',
            'statutoryBrackets.*.up_to' => 'nullable|numeric|min:0',
        ], attributes: [
            'statutoryForm.regime_id' => __('compensation::dashboard.fields.regime'),
            'statutoryForm.component_code' => __('compensation::dashboard.statutory.component'),
            'statutoryForm.payer' => __('compensation::dashboard.statutory.payer'),
            'statutoryForm.base' => __('compensation::dashboard.statutory.base'),
            'statutoryForm.effective_from' => __('compensation::dashboard.fields.effective_from'),
            'statutoryBrackets' => __('compensation::dashboard.statutory.brackets'),
            'statutoryBrackets.*.rate' => __('compensation::dashboard.statutory.rate'),
            'statutoryBrackets.*.up_to' => __('compensation::dashboard.statutory.up_to'),
        ])['statutoryForm'];

        $service->createStatutoryRate($data, $this->statutoryBrackets);

        $this->resetStatutoryForm();
        $this->announce('saved');
    }

    public function resetStatutoryForm(): void
    {
        $this->statutoryForm = ['regime_id' => null, 'component_code' => 'income_tax', 'payer' => 'ee', 'base' => 'social', 'effective_from' => ''];
        $this->statutoryBrackets = [];
        $this->panel = '';
        $this->resetValidation();
    }

    public function deleteStatutoryRate(int $id, CompensationCatalogService $service): void
    {
        $this->guardManage();
        $service->deleteStatutoryRate($id);
        $this->announce('deleted');
    }
}
