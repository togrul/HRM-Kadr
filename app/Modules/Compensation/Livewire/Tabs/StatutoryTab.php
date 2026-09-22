<?php

namespace App\Modules\Compensation\Livewire\Tabs;

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

    protected function viewName(): string
    {
        return 'statutory';
    }

    protected function panels(): array
    {
        return ['statutory'];
    }

    protected function resetPanel(string $panel): void
    {
        $this->resetStatutoryForm();
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
