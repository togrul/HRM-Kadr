<?php

namespace App\Modules\Orders\Support\Traits;

use App\Livewire\Forms\Orders\OrderForm;
use App\Livewire\Forms\Orders\OrderSearchForm;
use App\Livewire\Forms\Orders\SelectedPersonnelForm;
use App\Models\Component;
use App\Models\Order;
use App\Models\PersonnelBusinessTrip;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\PersonnelLookupReadRepository;
use App\Modules\Orders\Support\Traits\Orders\DropdownLabelCache;
use App\Modules\Orders\Support\Traits\Orders\HandlesOrderComponentFieldState;
use App\Modules\Orders\Support\Traits\Orders\HandlesOrderVacancy;
use App\Modules\Orders\Support\Traits\Orders\ManagesOrderComponents;
use App\Modules\Orders\Support\Traits\SRP\BladeDataPreparation;
use App\Modules\Orders\Support\Traits\Validations\OrderValidationTrait;
use App\Services\Orders\OrderAttributeMappingService;
use App\Services\Orders\OrderAttributePersister;
use App\Services\Orders\OrderComponentPersister;
use App\Services\Orders\OrderCrudPipelineService;
use App\Services\Orders\OrderInteractionStateService;
use App\Services\Orders\OrderLookupService;
use App\Services\Orders\OrderPersonnelPersister;
use App\Services\Orders\OrderRenderStateService;
use App\Services\Orders\OrderTemplateFormSchemaService;
use App\Services\Orders\VacancyDiffService;
use App\Services\Orders\VacationCleanupService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;

trait OrderCrud
{
    use BladeDataPreparation;
    use CallSwalTrait;
    use DropdownLabelCache;
    use HandlesOrderComponentFieldState;
    use HandlesOrderVacancy;
    use ManagesOrderComponents;
    use OrderValidationTrait;

    protected OrderLookupService $orderLookupService;

    protected OrderTypeStatusLookupReadRepository $orderTypeStatusLookup;

    protected PersonnelLookupReadRepository $personnelLookupReadRepository;

    protected OrderComponentPersister $componentPersister;

    protected OrderCrudPipelineService $crudPipelineService;

    protected OrderPersonnelPersister $personnelPersister;

    protected OrderRenderStateService $renderStateService;

    protected OrderInteractionStateService $interactionStateService;

    protected OrderTemplateFormSchemaService $templateFormSchemaService;

    protected OrderAttributeMappingService $attributeMappingService;

    protected OrderAttributePersister $attributePersister;

    protected VacancyDiffService $vacancyDiffService;

    protected VacationCleanupService $vacationCleanupService;

    /**
     * Cheap in-memory cache for lookup datasets that rarely change during a component lifecycle.
     */
    public OrderForm $orderForm;

    public OrderSearchForm $search;

    public SelectedPersonnelForm $selectedPersonnel;

    public string $title;

    //edited data model
    public $orderModel;

    //selected order category - from all order list
    public ?int $selectedOrder = null;

    //template secilende asagida komponentlerin gorunusu
    public $showComponent = false;

    //secilen sablon modelin ID si
    public $selectedTemplate;

    public array $originalComponents = [];

    public $selectedBlade;

    /**
     * Cached component definitions keyed by id so we can avoid re-querying when
     * emitting componentSelected events.
     *
     * @var array<int, array{id:int,dynamic_fields:string|null}>
     */
    protected array $componentDefinitions = [];

    public array $templateRowFieldKeys = [];

    public array $templateRowGroups = [];

    public array $templateSectionBlocks = [];

    public array $dynamicFieldCatalog = [];

    public array $dynamicDropdownFields = [];

    protected ?int $resolvedTemplateSchemaOrderTypeId = null;

    public string $templateSchemaSource = 'metadata_required';

    public array $loadedOptionGroups = [];

    public function bootOrderCrud(
        OrderLookupService $orderLookupService,
        OrderTypeStatusLookupReadRepository $orderTypeStatusLookup,
        PersonnelLookupReadRepository $personnelLookupReadRepository,
        OrderComponentPersister $componentPersister,
        OrderCrudPipelineService $crudPipelineService,
        OrderPersonnelPersister $personnelPersister,
        OrderRenderStateService $renderStateService,
        OrderInteractionStateService $interactionStateService,
        OrderTemplateFormSchemaService $templateFormSchemaService,
        OrderAttributeMappingService $attributeMappingService,
        OrderAttributePersister $attributePersister,
        VacancyDiffService $vacancyDiffService,
        VacationCleanupService $vacationCleanupService
    ): void {
        $this->orderLookupService = $orderLookupService;
        $this->orderTypeStatusLookup = $orderTypeStatusLookup;
        $this->personnelLookupReadRepository = $personnelLookupReadRepository;
        $this->componentPersister = $componentPersister;
        $this->crudPipelineService = $crudPipelineService;
        $this->personnelPersister = $personnelPersister;
        $this->renderStateService = $renderStateService;
        $this->interactionStateService = $interactionStateService;
        $this->templateFormSchemaService = $templateFormSchemaService;
        $this->attributeMappingService = $attributeMappingService;
        $this->attributePersister = $attributePersister;
        $this->vacancyDiffService = $vacancyDiffService;
        $this->vacationCleanupService = $vacationCleanupService;
    }

    public function setStructure($id, $list = null, $field = null, $key = null, $isCoded = null): void
    {
        $selection = $this->interactionStateService->resolveStructureSelection(
            id: $id,
            list: $list,
            field: $field,
            key: $key,
            isCoded: $isCoded,
            structureLineageResolver: fn (int $structureId) => $this->structureLineage($structureId),
            structureLabelBuilder: fn (array $lineage, bool $coded) => $this->buildStructureSelectedValue($lineage, $coded)
        );

        if ($selection === null) {
            return;
        }

        $this->{$selection['list']}[$selection['key']][$selection['field']] = $selection['id'];

        $this->registerComponentOptionLabels($selection['field'], [[
            'id' => $selection['id'],
            'label' => $selection['label'],
        ]], true);
    }

    #[On('componentSelected')]
    public function componentSelected($componentId, $rowKey = null): void
    {
        if (! empty($this->templateRowFieldKeys)) {
            $this->selectedComponents[$rowKey] = $this->templateRowFieldKeys;
            return;
        }

        if ($this->templateSchemaSource === 'metadata_required') {
            $this->selectedComponents[$rowKey] = [];
            return;
        }

        $this->selectedComponents[$rowKey] = $this->interactionStateService->resolveSelectedComponentFields(
            componentId: $componentId,
            componentDefinitions: $this->componentDefinitions,
            dynamicFieldsFallbackResolver: fn (int $resolvedId) => Component::find($resolvedId)?->dynamic_fields
        );
    }

    #[On('templateSelected')]
    public function templateSelected($value): void
    {
        $selection = $this->interactionStateService->resolveTemplateSelection(
            value: $value,
            selectedOrder: $this->selectedOrder,
            orderTypeResolver: fn (int $templateId) => $this->resolveTemplateOrderContext($templateId)
        );

        $this->showComponent = $selection['showComponent'];
        $this->selectedTemplate = $selection['selectedTemplate'];
        $this->resetComponentState();

        if ($selection['orderId'] !== null) {
            $this->orderForm->order_id = $selection['orderId'];
        }

        if ($selection['selectedBlade'] !== null) {
            $this->selectedBlade = $selection['selectedBlade'];
        }

        $this->refreshTemplateFormSchema();
        $this->reset('selectedComponents');
        $this->fillEmptyComponent();
    }

    public function updatedOrderFormOrderTypeId($value): void
    {
        $this->templateSelected((int) $value);
    }

    public function loadOptionGroup(string $group): void
    {
        $normalized = trim($group);

        if ($normalized === '') {
            return;
        }

        $this->loadedOptionGroups[$normalized] = true;
        $this->dispatch('ui-select-option-group-loaded', group: $normalized);
    }

    protected function markOptionGroupLoaded(string $group): void
    {
        $normalized = trim($group);

        if ($normalized === '') {
            return;
        }

        $this->loadedOptionGroups[$normalized] = true;
    }

    #[Computed]
    public function templateOptions(): array
    {
        $shouldLoadTemplates = ($this->loadedOptionGroups['templates'] ?? false)
            || filled($this->search->template)
            || filled($this->orderForm->order_type_id)
            || filled($this->selectedTemplate);

        if (! $shouldLoadTemplates) {
            return [];
        }

        $collection = $this->orderLookupService
            ->templates($this->selectedOrder ?? null, $this->search->template)
            ->map(fn ($template) => [
                'id' => $template->id,
                'label' => trim((string) $template->name),
            ]);

        $selected = $this->orderForm->order_type_id;
        if ($selected && ! $collection->contains(fn ($option) => (int) $option['id'] === (int) $selected)) {
            $label = $this->orderTypeStatusLookup->orderTypeNameById((int) $selected);
            if ($label) {
                $collection->prepend([
                    'id' => $selected,
                    'label' => trim((string) $label),
                ]);
            }
        }

        return $collection->unique('id')->values()->all();
    }

    protected function updatePersonnelName($value, $rowKey): void
    {
        $resolved = $this->interactionStateService->resolvePersonnelName(
            value: $value,
            orderId: (int) ($this->orderForm->order_id ?? 0),
            candidateOrderId: Order::IG_EMR,
            candidateResolver: fn (int $id) => $this->personnelLookupReadRepository->findCandidateNameParts($id),
            personnelResolver: fn (int $id) => $this->personnelLookupReadRepository->findPersonnelNameParts($id)
        );

        if ($resolved) {
            $this->componentForms[$rowKey]['name'] = $resolved['name'];
            $this->componentForms[$rowKey]['surname'] = $resolved['surname'];
        }
    }

    protected function modifyComponentList(array $componentForms): array
    {
        return $this->attributeMappingService->mapComponentAttributes(
            componentForms: $componentForms,
            attributeValueResolver: fn (string $field, $value, ?int $row) => $this->attributeValuePayload($field, $value, $row)
        );
    }

    private function fillCrudData(): array|\Livewire\Features\SupportEvents\Event
    {
        if (! $this->ensureTemplateSchemaReadyForSave()) {
            return $this->dispatch('addError', __('orders::order_form.messages.active_metadata_required'));
        }

        $data = $this->crudPipelineService->validateAndPrepare(
            selectedBlade: (string) $this->selectedBlade,
            validationRules: $this->validationRules(),
            validate: fn (array $rules) => $this->validate($rules),
            prepareDefaultBladeData: fn () => $this->prepareDefaultBladeData(),
            prepareBusinessTripBladeData: fn () => $this->prepareBusinessTripBladeData(),
            prepareVacationBladeData: fn () => $this->prepareVacationBladeData(),
            resolveVacancyData: fn (array $bladeData) => $this->resolveVacancyData($bladeData),
        );
        $message = $data['message'];

        if (! empty($message)) {
            return $this->dispatch('checkVacancyWasSet', $message);
        }

        return [
            'attributes' => $data['attributes'],
            'personnel_ids' => $data['personnel_ids'],
            'component_ids' => $data['component_ids'],
            'vacancy_list' => $data['vacancy_list'],
        ];
    }

    protected function ensureTemplateSchemaReadyForSave(): bool
    {
        $orderTypeId = (int) ($this->selectedTemplate ?? $this->orderForm->order_type_id ?? 0);
        if ($orderTypeId <= 0) {
            return true;
        }

        return $this->templateSchemaSource !== 'metadata_required';
    }

    private function diffVacancyPayload(array $current, array $original): array
    {
        return $this->vacancyDiffService->diff($current, $original);
    }

    public function mount()
    {
        if (! empty($this->orderModel)) {
            $this->authorize('edit-orders');
            $this->title = __('orders::order_form.titles.edit');
            $this->fillOrder();
            $this->refreshTemplateFormSchema();
        } else {
            $this->authorize('add-orders');
            $this->title = __('orders::order_form.titles.add');
            $this->orderForm->fillDefaults($this->selectedOrder, cache('settings'));
            $this->resetComponentState();
            $this->selectedPersonnel->resetState();
            $this->refreshTemplateFormSchema();
        }
    }

    private function saveAttribute($orderModel, $_attributes, $method): void
    {
        $this->attributePersister->persist($orderModel, $_attributes, $method);

        if ($method == 'update' && in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP])) {
            $this->vacationCleanupService->handle(
                $orderModel,
                collect($_attributes),
                $this->selectedBlade,
                $this->selectedPersonnel->personnels
            );
        }
    }

    #[Isolate]
    public function getStatusesProperty()
    {
        $locale = config('app.locale');

        return Cache::remember(
            "order_statuses:{$locale}",
            now()->addMinutes(10),
            fn () => $this->orderTypeStatusLookup->localizedStatuses((string) $locale)
        );
    }

    private function isForeignBusinessTrip(): bool
    {
        return $this->selectedBlade === Order::BLADE_BUSINESS_TRIP && $this->selectedTemplate == PersonnelBusinessTrip::FOREIGN_BUSINESS_TRIP;
    }

    private function isInternalBusinessTrip(): bool
    {
        return $this->selectedBlade === Order::BLADE_BUSINESS_TRIP && $this->selectedTemplate == PersonnelBusinessTrip::INTERNAL_BUSINESS_TRIP;
    }

    protected function isCandidateOrder(): bool
    {
        return ($this->orderForm->order_id ?? null) == Order::IG_EMR;
    }

    public function render()
    {
        $this->modifyCodedList();

        $lookup = $this->resolveLookupCollections();
        $this->syncSelectedComponentsFromLookup();

        $payload = $this->renderStateService->buildRenderPayload(
            $lookup,
            $this->selectedBlade,
            $this->personnel_name,
            $this->selectedPersonnel->personnels,
            registerOptionLabels: fn ($field, array $options) => $this->registerComponentOptionLabels($field, $options),
            personnelLabelResolver: fn ($person) => $this->personnelOptionLabel($person),
        );

        $this->registerTemplateDropdownOptionLabels($payload);
        $payload['dynamicFieldCatalog'] = $this->dynamicFieldCatalog;

        $viewName = ! empty($this->orderModel)
            ? 'orders::livewire.orders.edit-order'
            : 'orders::livewire.orders.add-order';

        return view($viewName, $payload);
    }

    protected function resolveLookupCollections(): array
    {
        $personnelIdList = array_filter(
            collect($this->componentForms)->pluck('personnel_id')->toArray(),
            static fn ($value) => $value !== null
        );
        $componentIdList = array_filter(
            collect($this->componentForms)->pluck('component_id')->toArray(),
            static fn ($value) => $value !== null
        );
        $selectedDropdownValues = collect(['rank_id', 'structure_main_id', 'structure_id', 'position_id'])
            ->mapWithKeys(fn (string $field) => [
                $field => collect($this->componentForms)
                    ->pluck($field)
                    ->filter(static fn ($value) => $value !== null && $value !== '')
                    ->map(static fn ($value) => (int) $value)
                    ->filter(static fn (int $value) => $value > 0)
                    ->unique()
                    ->values()
                    ->all(),
            ])
            ->all();

        $isCandidateOrder = $this->isCandidateOrder();
        $selectedOrder = $this->selectedOrder ?? null;
        $selectedTemplate = $this->selectedTemplate;
        $searchTemplate = $this->search->template ?? '';
        $searchPersonnel = $this->search->personnel ?? '';
        $searchRank = $this->search->rank ?? '';
        $searchMainStructure = $this->search->mainStructure ?? '';
        $searchStructure = $this->search->structure ?? '';
        $searchPosition = $this->search->position ?? '';
        $visibleFields = $this->visibleLookupFields();

        return $this->renderStateService->resolveLookupCollections(
            isCandidateOrder: $isCandidateOrder,
            selectedOrder: $selectedOrder,
            selectedTemplate: $selectedTemplate,
            searchTemplate: $searchTemplate,
            searchPersonnel: $searchPersonnel,
            searchRank: $searchRank,
            searchMainStructure: $searchMainStructure,
            searchStructure: $searchStructure,
            searchPosition: $searchPosition,
            personnelIdList: $personnelIdList,
            componentIdList: $componentIdList,
            selectedDropdownValues: $selectedDropdownValues,
            loadedOptionGroups: $this->loadedOptionGroups,
            visibleFields: $visibleFields,
            rememberComponentDefinitions: fn ($componentForms) => $this->rememberComponentDefinitions($componentForms)
        );
    }

    protected function visibleLookupFields(): array
    {
        $fields = collect($this->selectedComponents ?? [])
            ->flatten()
            ->map(function ($token) {
                $normalizedToken = (string) $token;
                $resolvedField = data_get($this->dynamicFieldCatalog, $normalizedToken.'.field');

                if (is_string($resolvedField) && trim($resolvedField) !== '') {
                    return trim($resolvedField);
                }

                return match (ltrim($normalizedToken, '$')) {
                    'rank' => 'rank_id',
                    'fullname' => 'personnel_id',
                    'structure_main' => 'structure_main_id',
                    'structure' => 'structure_id',
                    'position' => 'position_id',
                    default => ltrim($normalizedToken, '$'),
                };
            })
            ->filter()
            ->values();

        if ($this->selectedBlade === Order::BLADE_DEFAULT) {
            $fields->push('personnel_id');
        }

        if (($this->showComponent ?? false) && ($this->selectedTemplate || $this->orderForm->order_type_id)) {
            $fields->push('component_id');
        }

        return $fields
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{order_id:int|null,selected_blade:string|null}
     */
    protected function resolveTemplateOrderContext(int $templateId): array
    {
        $order = $this->orderTypeStatusLookup->findOrderType($templateId, ['order']);

        if (! $order) {
            return [
                'order_id' => null,
                'selected_blade' => null,
            ];
        }

        return [
            'order_id' => (int) $order->order_id,
            'selected_blade' => $order->order?->blade,
        ];
    }

    protected function refreshTemplateFormSchema(bool $force = false): void
    {
        $orderTypeId = (int) ($this->selectedTemplate ?? $this->orderForm->order_type_id ?? 0);

        if (
            ! $force
            && $this->resolvedTemplateSchemaOrderTypeId === $orderTypeId
            && ! empty($this->dynamicFieldCatalog)
        ) {
            return;
        }

        $resolved = $this->templateFormSchemaService->resolveForOrderType($orderTypeId);

        $this->templateSchemaSource = (string) ($resolved['source'] ?? 'metadata_required');
        $this->templateRowFieldKeys = $resolved['row_field_keys'] ?? [];
        $this->templateRowGroups = $resolved['row_groups'] ?? [];
        $this->templateSectionBlocks = $resolved['section_blocks'] ?? [];
        $this->dynamicFieldCatalog = $resolved['field_catalog'] ?? [];
        $this->dynamicDropdownFields = $resolved['dropdown_fields'] ?? [];
        $this->resolvedTemplateSchemaOrderTypeId = $orderTypeId;
    }

    protected function registerTemplateDropdownOptionLabels(array $payload): void
    {
        foreach ($this->dynamicFieldCatalog as $definition) {
            $field = (string) ($definition['field'] ?? '');
            $modelKey = (string) ($definition['model'] ?? '');
            $input = (string) ($definition['input'] ?? '');

            if ($field === '' || $modelKey === '' || ! in_array($input, ['select'], true)) {
                continue;
            }

            $options = collect($payload[$modelKey] ?? [])
                ->map(function ($item) {
                    $id = data_get($item, 'id');
                    $label = data_get($item, 'label', data_get($item, 'name'));

                    if ($id === null || $label === null || $label === '') {
                        return null;
                    }

                    return [
                        'id' => (int) $id,
                        'label' => (string) $label,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            if (! empty($options)) {
                $this->registerComponentOptionLabels($field, $options);
            }
        }
    }
}
