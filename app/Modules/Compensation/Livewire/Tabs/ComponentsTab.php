<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Models\CompensationComponent;
use App\Modules\Compensation\Application\Services\CompensationCatalogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

/**
 * The pay component catalog (earnings and deductions).
 */
class ComponentsTab extends CompensationTab
{
    use WithPagination;

    #[Locked]
    public ?int $editingComponentId = null;

    public string $componentSearch = '';

    public array $componentForm = [
        'code' => '',
        'name' => '',
        'type' => 'earning',
        'calc_type' => 'fixed',
        'taxable' => true,
        'affects_social' => true,
        'is_statutory' => false,
        'gl_code' => '',
        'sort' => 0,
        'is_active' => true,
    ];

    protected function viewName(): string
    {
        return 'components';
    }

    protected function panels(): array
    {
        return ['component'];
    }

    protected function resetPanel(string $panel): void
    {
        $this->cancelComponent();
    }

    #[Computed]
    public function components(): LengthAwarePaginator
    {
        $term = trim($this->componentSearch);

        return CompensationComponent::query()
            ->when($term !== '', fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%"))
            ->orderBy('sort')
            ->paginate(10, ['*'], 'componentsPage');
    }

    public function updatedComponentSearch(): void
    {
        $this->resetPage('componentsPage');
    }

    public function editComponent(int $id): void
    {
        $component = CompensationComponent::findOrFail($id);
        $this->editingComponentId = $component->id;
        $this->panel = 'component';
        $this->componentForm = [
            'code' => $component->code,
            'name' => $component->name,
            'type' => $component->type,
            'calc_type' => $component->calc_type,
            'taxable' => (bool) $component->taxable,
            'affects_social' => (bool) $component->affects_social,
            'is_statutory' => (bool) $component->is_statutory,
            'gl_code' => $component->gl_code ?? '',
            'sort' => $component->sort,
            'is_active' => (bool) $component->is_active,
        ];
        $this->resetValidation();
    }

    public function saveComponent(CompensationCatalogService $service): void
    {
        $this->guardManage();

        $codeRule = 'required|string|max:64|unique:compensation_components,code';
        if ($this->editingComponentId) {
            $codeRule .= ','.$this->editingComponentId;
        }

        $data = $this->validate([
            'componentForm.code' => $codeRule,
            'componentForm.name' => 'required|string|max:255',
            'componentForm.type' => 'required|in:earning,deduction',
            'componentForm.calc_type' => 'required|in:fixed,percent,formula,per_diem,rate',
            'componentForm.taxable' => 'boolean',
            'componentForm.affects_social' => 'boolean',
            'componentForm.is_statutory' => 'boolean',
            'componentForm.gl_code' => 'nullable|string|max:64',
            'componentForm.sort' => 'nullable|integer|min:0',
            'componentForm.is_active' => 'boolean',
        ], attributes: $this->fieldLabels([
            'componentForm.code' => 'code',
            'componentForm.name' => 'name',
            'componentForm.type' => 'type',
            'componentForm.calc_type' => 'calc_type',
            'componentForm.taxable' => 'taxable',
            'componentForm.affects_social' => 'affects_social',
            'componentForm.is_statutory' => 'is_statutory',
            'componentForm.gl_code' => 'gl_code',
            'componentForm.sort' => 'sort',
            'componentForm.is_active' => 'is_active',
        ]))['componentForm'];

        $service->saveComponent($data, $this->editingComponentId);

        $this->cancelComponent();
        $this->announce('saved');
    }

    public function deleteComponent(int $id, CompensationCatalogService $service): void
    {
        $this->guardManage();
        $service->deleteComponent($id);

        if ($this->editingComponentId === $id) {
            $this->cancelComponent();
        }

        $this->resetPage('componentsPage');
        $this->announce('deleted');
    }

    public function cancelComponent(): void
    {
        $this->editingComponentId = null;
        $this->componentForm = [
            'code' => '', 'name' => '', 'type' => 'earning', 'calc_type' => 'fixed',
            'taxable' => true, 'affects_social' => true, 'is_statutory' => false,
            'gl_code' => '', 'sort' => 0, 'is_active' => true,
        ];
        $this->panel = '';
        $this->resetValidation();
    }
}
