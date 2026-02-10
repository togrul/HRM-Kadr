<?php

namespace App\Modules\Orders\Support\Traits;

use App\Livewire\Forms\Orders\OrderForm;
use App\Livewire\Forms\Orders\OrderSearchForm;
use App\Livewire\Forms\Orders\SelectedPersonnelForm;
use App\Models\Candidate;
use App\Models\Component;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
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

    protected OrderComponentPersister $componentPersister;

    protected OrderCrudPipelineService $crudPipelineService;

    protected OrderPersonnelPersister $personnelPersister;

    protected OrderRenderStateService $renderStateService;

    protected OrderInteractionStateService $interactionStateService;

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
    public ?int $selectedOrder;

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

    public function bootOrderCrud(
        OrderLookupService $orderLookupService,
        OrderComponentPersister $componentPersister,
        OrderCrudPipelineService $crudPipelineService,
        OrderPersonnelPersister $personnelPersister,
        OrderRenderStateService $renderStateService,
        OrderInteractionStateService $interactionStateService,
        OrderAttributeMappingService $attributeMappingService,
        OrderAttributePersister $attributePersister,
        VacancyDiffService $vacancyDiffService,
        VacationCleanupService $vacationCleanupService
    ): void {
        $this->orderLookupService = $orderLookupService;
        $this->componentPersister = $componentPersister;
        $this->crudPipelineService = $crudPipelineService;
        $this->personnelPersister = $personnelPersister;
        $this->renderStateService = $renderStateService;
        $this->interactionStateService = $interactionStateService;
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
            structureLabelBuilder: fn (array $lineage, bool $coded) => $this->buildStructureValue($lineage, $coded)
        );

        if ($selection === null) {
            return;
        }

        $this->{$selection['list']}[$selection['key']][$selection['field']] = $selection['id'];

        $this->registerComponentOptionLabels($selection['field'], [[
            'id' => $selection['id'],
            'label' => $selection['label'],
        ]]);
    }

    #[On('componentSelected')]
    public function componentSelected($componentId, $rowKey = null): void
    {
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

        $this->reset('selectedComponents');
        $this->fillEmptyComponent();
    }

    public function updatedOrderFormOrderTypeId($value): void
    {
        $this->templateSelected((int) $value);
    }

    #[Computed(persist: true)]
    public function templateOptions(): array
    {
        $collection = $this->orderLookupService
            ->templates($this->selectedOrder ?? null, $this->search->template)
            ->map(fn ($template) => [
                'id' => $template->id,
                'label' => trim((string) $template->name),
            ]);

        $selected = $this->orderForm->order_type_id;
        if ($selected && ! $collection->contains(fn ($option) => (int) $option['id'] === (int) $selected)) {
            $label = optional(OrderType::find($selected))->name;
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
            candidateResolver: fn (int $id) => Candidate::find($id),
            personnelResolver: fn (int $id) => Personnel::find($id)
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

    private function diffVacancyPayload(array $current, array $original): array
    {
        return $this->vacancyDiffService->diff($current, $original);
    }

    public function mount()
    {
        if (! empty($this->orderModel)) {
            $this->authorize('edit-orders');
            $this->title = __('Edit order');
            $this->fillOrder();
        } else {
            $this->authorize('add-orders');
            $this->title = __('Add order');
            $this->orderForm->fillDefaults($this->selectedOrder, cache('settings'));
            $this->resetComponentState();
            $this->selectedPersonnel->resetState();
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

        return Cache::remember("order_statuses:{$locale}", now()->addMinutes(10), function () use ($locale) {
            return OrderStatus::where('locale', $locale)->get();
        });
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

        $isCandidateOrder = $this->isCandidateOrder();
        $selectedOrder = $this->selectedOrder ?? null;
        $selectedTemplate = $this->selectedTemplate;
        $searchTemplate = $this->search->template ?? '';
        $searchPersonnel = $this->search->personnel ?? '';
        $searchStructure = $this->search->structure ?? '';
        $searchPosition = $this->search->position ?? '';
        $needsPersonnelLookup = $this->selectedBlade === Order::BLADE_DEFAULT;

        return $this->renderStateService->resolveLookupCollections(
            needsPersonnelLookup: $needsPersonnelLookup,
            isCandidateOrder: $isCandidateOrder,
            selectedOrder: $selectedOrder,
            selectedTemplate: $selectedTemplate,
            searchTemplate: $searchTemplate,
            searchPersonnel: $searchPersonnel,
            searchStructure: $searchStructure,
            searchPosition: $searchPosition,
            personnelIdList: $personnelIdList,
            rememberComponentDefinitions: fn ($componentForms) => $this->rememberComponentDefinitions($componentForms)
        );
    }

    /**
     * @return array{order_id:int|null,selected_blade:string|null}
     */
    protected function resolveTemplateOrderContext(int $templateId): array
    {
        $order = OrderType::with('order')
            ->where('id', $templateId)
            ->first();

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
}
