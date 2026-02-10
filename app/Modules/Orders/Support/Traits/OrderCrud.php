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
use App\Modules\Orders\Support\Traits\Orders\ResolvesOrderLookups;
use App\Modules\Orders\Support\Traits\SRP\BladeDataPreparation;
use App\Modules\Orders\Support\Traits\Validations\OrderValidationTrait;
use App\Services\Orders\OrderAttributePersister;
use App\Services\Orders\OrderComponentPersister;
use App\Services\Orders\OrderCrudPipelineService;
use App\Services\Orders\OrderLookupService;
use App\Services\Orders\OrderPersonnelPersister;
use App\Services\Orders\OrderRenderPayloadBuilder;
use App\Services\Orders\VacancyDiffService;
use App\Services\Orders\VacationCleanupService;
use Illuminate\Support\Collection;
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
    use ResolvesOrderLookups;

    protected OrderLookupService $orderLookupService;

    protected OrderComponentPersister $componentPersister;

    protected OrderCrudPipelineService $crudPipelineService;

    protected OrderPersonnelPersister $personnelPersister;

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
        OrderPersonnelPersister $personnelPersister
    ): void {
        $this->orderLookupService = $orderLookupService;
        $this->componentPersister = $componentPersister;
        $this->crudPipelineService = $crudPipelineService;
        $this->personnelPersister = $personnelPersister;
    }

    public function setStructure($id, $list = null, $field = null, $key = null, $isCoded = null)
    {
        // Allow payload array/object for convenience
        if (is_array($id)) {
            $list = $id['list'] ?? $list;
            $field = $id['field'] ?? $field;
            $key = $id['row'] ?? $id['key'] ?? $key;
            $isCoded = $id['coded'] ?? $isCoded;
            $id = $id['id'] ?? null;
        }

        $id = (int) $id;
        $lineage = $this->structureLineage($id);

        $label = $this->buildStructureValue($lineage, (bool) $isCoded);

        if ($field === 'structure_id' && ! $isCoded) {
            $label = optional(collect($lineage)->last())['name'] ?? $label;
        }

        if ($list === null || $field === null || $key === null) {
            return;
        }

        $this->{$list}[$key][$field] = $id;

        $this->registerComponentOptionLabels($field, [[
            'id' => $id,
            'label' => $label,
        ]]);
    }

    protected function cachedLookup(string $key, callable $resolver)
    {
        if (! array_key_exists($key, $this->staticLookups)) {
            $this->staticLookups[$key] = $resolver();
        }

        return $this->staticLookups[$key];
    }

    protected function memoizedLookup(string $key, array $context, callable $resolver)
    {
        $hash = $key.'::'.md5(serialize($context));

        if (! array_key_exists($hash, $this->lookupCache)) {
            $this->lookupCache[$hash] = $resolver();
        }

        return $this->lookupCache[$hash];
    }

    #[On('componentSelected')]
    public function componentSelected($componentId, $rowKey = null)
    {
        $componentId = $componentId !== null ? (int) $componentId : null;

        if (! $componentId) {
            $this->selectedComponents[$rowKey] = [];

            return;
        }

        $definition = $this->componentDefinitions[$componentId]['dynamic_fields'] ?? null;

        if ($definition === null) {
            $component = Component::find($componentId);
            $definition = $component?->dynamic_fields;
        }

        $this->selectedComponents[$rowKey] = $definition
            ? array_filter(explode(',', (string) $definition))
            : [];
    }

    #[On('templateSelected')]
    public function templateSelected($value)
    {
        $this->showComponent = $value > 0;
        $this->selectedTemplate = $value;
        $this->resetComponentState();
        if (empty($this->selectedOrder)) {
            $order = OrderType::with('order')
                ->where('id', $value)
                ->first();

            if ($order) {
                $this->orderForm->order_id = $order->order_id;
                $this->selectedBlade = $order->order->blade;
            }
        }
        $this->reset('selectedComponents');
        $this->fillEmptyComponent();
    }

    public function updatedOrderFormOrderTypeId($value)
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

    protected function updatePersonnelName($value, $rowKey)
    {
        // yoxlamaq lazimdir acilan kimi gorur order table i yoxsa yox.
        $personnelModel = $this->orderForm->order_id == Order::IG_EMR
            ? Candidate::find($value)
            : Personnel::find($value);

        if ($personnelModel) {
            $this->componentForms[$rowKey]['name'] = $personnelModel->name;
            $this->componentForms[$rowKey]['surname'] = $personnelModel->surname;
        }
    }

    protected function modifyComponentList(array $componentForms): array
    {
        $_modified_component = [];
        foreach ($componentForms as $key => $component) {
            foreach ($component as $keyComponent => $valueComponent) {
                $_edit_key = match ($keyComponent) {
                    'rank_id' => '$rank',
                    'personnel_id' => '$fullname',
                    'structure_main_id' => '$structure_main',
                    'structure_id' => '$structure',
                    'position_id' => '$position',
                    'component_id', 'row' => $keyComponent,
                    default => '$'.$keyComponent
                };

                $_modified_component[$key][$_edit_key] = $this->attributeValuePayload($keyComponent, $valueComponent, $key);
            }
        }

        return $_modified_component;
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
        return (new VacancyDiffService)->diff($current, $original);
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
        (new OrderAttributePersister)->persist($orderModel, $_attributes, $method);

        if ($method == 'update' && in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP])) {
            (new VacationCleanupService)->handle(
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

        $payload = app(OrderRenderPayloadBuilder::class)->build(
            $lookup,
            $this->selectedBlade,
            $this->personnel_name,
            $this->selectedPersonnel->personnels,
            registerOptionLabels: fn ($field, array $options) => $this->registerComponentOptionLabels($field, $options),
            personnelLabelResolver: fn ($person) => $this->personnelOptionLabel($person)
        );

        $viewName = ! empty($this->orderModel)
            ? 'orders::livewire.orders.edit-order'
            : 'orders::livewire.orders.add-order';

        return view($viewName, $payload);
    }

    protected function syncSelectedComponentsFromLookup(): void
    {
        foreach ($this->componentForms as $row => $component) {
            $componentId = $component['component_id'] ?? null;
            if (empty($componentId)) {
                $this->selectedComponents[$row] = [];

                continue;
            }

            $definition = $this->componentDefinitions[(int) $componentId]['dynamic_fields'] ?? null;

            $this->selectedComponents[$row] = $definition
                ? array_filter(explode(',', (string) $definition))
                : [];
        }
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

        $componentForms = $this->memoizedLookup(
            'components',
            [$selectedTemplate],
            fn () => $this->orderLookupService->components($selectedTemplate)
        );

        $lookups = [
            'templates' => $this->memoizedLookup(
                'templates',
                [$selectedOrder, $searchTemplate],
                fn () => $this->orderLookupService->templates($selectedOrder, $searchTemplate)
            ),
            'components' => $componentForms,
            'personnels' => $needsPersonnelLookup
                ? $this->memoizedLookup(
                    'personnels',
                    [$isCandidateOrder, $personnelIdList, $searchPersonnel],
                    fn () => $this->orderLookupService->personnels($isCandidateOrder, $personnelIdList, $searchPersonnel)
                )
                : collect(),
            'ranks' => $this->cachedLookup('ranks', fn () => $this->orderLookupService->ranks()),
            'main_structures' => $this->cachedLookup('main_structures', fn () => $this->orderLookupService->mainStructures()),
            'structures' => $this->memoizedLookup(
                'structures',
                [$searchStructure],
                fn () => $this->orderLookupService->structures($searchStructure)
            ),
            'positions' => $this->memoizedLookup(
                'positions',
                [$searchPosition],
                fn () => $this->orderLookupService->positions($searchPosition)
            ),
        ];

        $this->rememberComponentDefinitions($componentForms);

        return $lookups;
    }

    protected function rememberComponentDefinitions(Collection $componentForms): void
    {
        $this->componentDefinitions = $componentForms
            ->mapWithKeys(fn ($component) => [
                (int) $component->id => [
                    'id' => (int) $component->id,
                    'dynamic_fields' => $component->dynamic_fields,
                ],
            ])
            ->all();
    }
}
