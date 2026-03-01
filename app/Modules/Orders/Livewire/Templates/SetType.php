<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
use App\Models\OrderType;
use App\Modules\Orders\Support\Traits\Templates\HandlesSetTypeMetadataBootstrap;
use App\Modules\Orders\Support\Traits\Templates\HandlesSetTypeUiConfigLifecycle;
use App\Modules\Orders\Support\Traits\Templates\HandlesSetTypeUiConfigMutations;
use App\Modules\Orders\Support\Traits\Templates\HandlesSetTypeUiConfigSupport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class SetType extends Component
{
    use AuthorizesRequests;
    use SideModalAction;
    use HandlesSetTypeUiConfigSupport;
    use HandlesSetTypeMetadataBootstrap;
    use HandlesSetTypeUiConfigLifecycle;
    use HandlesSetTypeUiConfigMutations;

    public $types = [];

    public $title;

    public $templateModel;

    public $selectedType;

    public $selectedModel;

    public ?int $uiConfigOrderTypeId = null;

    public ?int $uiConfigVersionId = null;

    public array $uiConfigFieldMeta = [];

    public array $uiConfigDraft = [];

    public array $sectionBlocksDraft = [];

    public array $mappingDraft = [];

    public array $uiPlaceholderCoverage = [];

    public array $uiConfigAuditTrail = [];

    public array $uiConfigVersions = [];

    public array $uiInputTypes = [];

    public string $newFieldKey = '';

    public string $newFieldLabel = '';

    public string $newFieldAlias = '';

    public string $newFieldInput = 'text-input';

    public string $newFieldModel = '';

    public string $newFieldSelectedName = '';

    public string $newFieldSearchField = '';

    public bool $newFieldRequired = false;

    public string $newFieldRules = '';

    public function rules()
    {
        return [
            'types.name' => 'required|string|min:2|unique:order_types,name',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'types.name' => __('Name'),
        ];
    }

    public function addType()
    {
        if (! $this->ensureTemplateUiPermission('set')) {
            return;
        }

        $this->validate();

        $this->templateModel->types()->create($this->types);

        $this->clearField();

        $this->dispatch('typesUpdated', __('Type was added successfully!'));
    }

    public function removeType($_typeId)
    {
        if (! $this->ensureTemplateUiPermission('set')) {
            return;
        }

        $type = $this->resolveOwnedType((int) $_typeId);
        if (! $type) {
            $this->dispatch('addError', __('Type not found.'));

            return;
        }

        $type->delete();

        if ((int) $this->selectedType === (int) $_typeId) {
            $this->clearField();
        }

        $this->dispatch('typesUpdated', __('Type was updated successfully!'));
    }

    public function editType($_typeId)
    {
        if (! $this->ensureTemplateUiPermission('set')) {
            return;
        }

        $this->selectedModel = $this->resolveOwnedType((int) $_typeId);
        if (! $this->selectedModel) {
            $this->dispatch('addError', __('Type not found.'));

            return;
        }

        $this->selectedType = (int) $_typeId;
        $this->types['name'] = $this->selectedModel->name;
    }

    public function updateModel()
    {
        if (! $this->ensureTemplateUiPermission('set')) {
            return;
        }

        $type = $this->resolveOwnedType((int) $this->selectedType);
        if (! $type) {
            $this->dispatch('addError', __('Type not found.'));

            return;
        }

        $type->update($this->types);
        $this->clearField();
        $this->dispatch('typesUpdated', __('Type was added successfully!'));
    }

    public function cancelUpdate()
    {
        $this->clearField();
    }

    protected function clearField()
    {
        $this->types = [];
        $this->selectedType = null;
        $this->resetValidation();
    }

    public function mount()
    {
        $this->title = __('Set Type');
        $this->templateModel = Order::findOrFail($this->templateModel);
        $this->uiInputTypes = $this->inputTypeOptions();
        $this->resetNewFieldDraft();
    }

    public function render()
    {
        $_order_types = $this->templateModel->types()
            ->with('templateSet.activeVersion')
            ->get();

        return view('orders::livewire.orders.templates.set-type', compact('_order_types'));
    }

    private function resolveOwnedType(int $typeId): ?OrderType
    {
        return $this->templateModel
            ->types()
            ->whereKey($typeId)
            ->first();
    }
}
