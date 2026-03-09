<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Livewire\Traits\SideModalAction;
use App\Modules\Orders\Application\UseCases\Templates\ManageSetTypeOrderTypesUseCase;
use App\Modules\Orders\Application\UseCases\Templates\SetTypeReadUseCase;
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
            'types.name' => __('orders::template_set_type.labels.name'),
        ];
    }

    public function addType()
    {
        if (! $this->ensureTemplateUiPermission('set')) {
            return;
        }

        $this->validate();

        app(ManageSetTypeOrderTypesUseCase::class)->addType($this->templateModel, $this->types);

        $this->clearField();

        $this->dispatch('typesUpdated', __('orders::template_set_type.messages.type_added'));
    }

    public function removeType($_typeId)
    {
        if (! $this->ensureTemplateUiPermission('set')) {
            return;
        }

        $removed = app(ManageSetTypeOrderTypesUseCase::class)
            ->removeType($this->templateModel, (int) $_typeId);

        if (! $removed) {
            $this->dispatch('addError', __('orders::template_set_type.messages.type_not_found'));

            return;
        }

        if ((int) $this->selectedType === (int) $_typeId) {
            $this->clearField();
        }

        $this->dispatch('typesUpdated', __('orders::template_set_type.messages.type_deleted'));
    }

    public function editType($_typeId)
    {
        if (! $this->ensureTemplateUiPermission('set')) {
            return;
        }

        $this->selectedModel = $this->resolveOwnedType((int) $_typeId);
        if (! $this->selectedModel) {
            $this->dispatch('addError', __('orders::template_set_type.messages.type_not_found'));

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

        $updated = app(ManageSetTypeOrderTypesUseCase::class)
            ->updateType($this->templateModel, (int) $this->selectedType, $this->types);

        if (! $updated) {
            $this->dispatch('addError', __('orders::template_set_type.messages.type_not_found'));

            return;
        }

        $this->clearField();
        $this->dispatch('typesUpdated', __('orders::template_set_type.messages.type_updated'));
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
        $this->title = __('orders::template_set_type.title');
        $this->templateModel = app(SetTypeReadUseCase::class)->loadTemplateOrFail((int) $this->templateModel);
        $this->uiInputTypes = $this->inputTypeOptions();
        $this->resetNewFieldDraft();
    }

    public function render()
    {
        $_order_types = app(SetTypeReadUseCase::class)->orderTypesWithActiveVersion((int) $this->templateModel->id);

        return view('orders::livewire.orders.templates.set-type', compact('_order_types'));
    }

    private function resolveOwnedType(int $typeId): ?\App\Models\OrderType
    {
        return app(SetTypeReadUseCase::class)->resolveOwnedType((int) $this->templateModel->id, $typeId);
    }
}
