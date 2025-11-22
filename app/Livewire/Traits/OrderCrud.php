<?php

namespace App\Livewire\Traits;

use App\Livewire\Forms\Orders\OrderForm;
use App\Livewire\Forms\Orders\OrderSearchForm;
use App\Livewire\Forms\Orders\SelectedPersonnelForm;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\Orders\DropdownLabelCache;
use App\Livewire\Traits\Orders\HandlesOrderVacancy;
use App\Livewire\Traits\Orders\ManagesOrderComponents;
use App\Livewire\Traits\Orders\ResolvesOrderLookups;
use App\Livewire\Traits\SRP\BladeDataPreparation;
use App\Livewire\Traits\Validations\OrderValidationTrait;
use App\Models\Candidate;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Component;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Rank;
use App\Services\CheckVacancyService;
use App\Services\Orders\OrderAttributePersister;
use App\Services\Orders\OrderComponentPersister;
use App\Services\Orders\OrderLookupService;
use App\Services\Orders\OrderPersonnelPersister;
use App\Services\Orders\OrderRenderPayloadBuilder;
use App\Services\Orders\VacancyDiffService;
use App\Services\Orders\VacationCleanupService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

trait OrderCrud
{
    use BladeDataPreparation;
    use OrderValidationTrait;
    use CallSwalTrait;
    use DropdownLabelCache;
    use ManagesOrderComponents;
    use ResolvesOrderLookups;
    use HandlesOrderVacancy;

    protected OrderLookupService $orderLookupService;
    protected OrderComponentPersister $componentPersister;
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

    public function updated($propertyName, $value)
    {
        if ($this->handleComponentPropertyMutation($propertyName, $value)) {
            return;
        }
    }
    protected function structureLabelForRow(?int $row, int $structureId): string
    {
        $lineage = $this->structureLineage($structureId);

        if (empty($lineage)) {
            return '---';
        }

        $isCoded = $row !== null ? (bool) ($this->coded_list[$row] ?? false) : false;

        if ($isCoded) {
            return $this->buildStructureValue($lineage, true);
        }

        return optional(collect($lineage)->last())['name'] ?? '---';
    }

    protected function resolveStructureLabel(int $id, bool $isCoded): string
    {
        $lineage = $this->structureLineage($id);

        if (empty($lineage)) {
            return '---';
        }

        return $this->buildStructureValue($lineage, $isCoded);
    }

    protected function optionsFromCollection(Collection $collection, callable $labelResolver): array
    {
        return $collection
            ->map(fn ($item) => [
                'id' => (int) data_get($item, 'id'),
                'label' => (string) $labelResolver($item),
            ])
            ->unique('id')
            ->values()
            ->all();
    }

    protected function normalizeVacancyEntries(array $entries): array
    {
        return collect($entries)
            ->map(function ($entry) {
                $structureId = $this->valueAsInt($entry, 'structure_id');
                $positionId = $this->valueAsInt($entry, 'position_id');

                if (! $structureId || ! $positionId) {
                    return null;
                }

                return [
                    'structure_id' => $structureId,
                    'position_id' => $positionId,
                    'structure_label' => $this->dropdownFieldLabel('structure_id', $structureId),
                    'position_label' => $this->dropdownFieldLabel('position_id', $positionId),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function valueAsInt($entry, string $field): ?int
    {
        $value = data_get($entry, $field);

        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        return $value !== null ? (int) $value : null;
    }

    protected function personnelOptionLabel($model): string
    {
        $parts = array_filter([
            data_get($model, 'surname'),
            data_get($model, 'name'),
            data_get($model, 'patronymic'),
        ]);

        if (! empty($parts)) {
            return trim(implode(' ', $parts));
        }

        return (string) data_get($model, 'fullname', '');
    }

    protected function attributeValuePayload(string $field, $value, int $row = null)
    {
        if (! $this->isDropdownField($field)) {
            return $value;
        }

        $id = $value !== null ? (int) $value : null;

        return [
            'id' => $id,
            'name' => $id ? $this->dropdownFieldLabel($field, $id, $row) : '---',
        ];
    }

    public function componentFieldLabel(int $row, string $field): string
    {
        $value = data_get($this->componentForms[$row] ?? [], $field);

        if (is_array($value) && array_key_exists('name', $value)) {
            return (string) $value['name'];
        }

        if ($this->isDropdownField($field)) {
            $resolvedValue = is_array($value) ? ($value['id'] ?? null) : $value;

            return $this->dropdownFieldLabel($field, $resolvedValue, $row);
        }

        return $value ?: '---';
    }

    public function componentFieldValue(int $row, string $field)
    {
        $value = data_get($this->componentForms[$row] ?? [], $field);

        if (is_array($value)) {
            return $value['id'] ?? null;
        }

        return $value;
    }

    protected function handleComponentPropertyMutation(string $propertyName, $value): bool
    {
        if (! str_starts_with($propertyName, 'componentForms.')) {
            return false;
        }

        $segments = explode('.', $propertyName);
        if (count($segments) < 3) {
            return false;
        }

        [, $rowIndex, $field] = $segments;
        $row = is_numeric($rowIndex) ? (int) $rowIndex : null;

        if ($row !== null && $this->isDropdownField($field)) {
            $value = $value !== null ? (int) $value : null;
            $this->componentForms[$row][$field] = $value;
        }

        if ($field === 'component_id') {
            $this->componentSelected($value, $row);

            return true;
        }

        if ($field === 'personnel_id' && $row !== null) {
            $this->updatePersonnelName($value, $row);
        }

        if ($field === 'structure_main_id' && $row !== null) {
            $this->coded_list[$row] = (int) $value === 1;
            $this->componentForms[$row]['structure_id'] = null;
            unset($this->componentForms[$row]['structure'], $this->componentForms[$row]['structure_name']);
        }

        if (in_array($field, ['start_date', 'end_date'], true) && $row !== null) {
            if (! empty($this->componentForms[$row]['start_date']) && ! empty($this->componentForms[$row]['end_date'])) {
                $start_dt = Carbon::createFromDate($this->componentForms[$row]['start_date']);
                $end_dt = Carbon::createFromDate($this->componentForms[$row]['end_date']);
                $this->componentForms[$row]['days'] = $start_dt->diffInDays($end_dt);
            }
        }

        return true;
    }

    public function bootOrderCrud(
        OrderLookupService $orderLookupService,
        OrderComponentPersister $componentPersister,
        OrderPersonnelPersister $personnelPersister
    ): void {
        $this->orderLookupService = $orderLookupService;
        $this->componentPersister = $componentPersister;
        $this->personnelPersister = $personnelPersister;
    }

    public function setStructure($id, $list, $field, $key, $isCoded)
    {
        $id = (int) $id;
        $lineage = $this->structureLineage($id);

        $label = $this->buildStructureValue($lineage, (bool) $isCoded);

        if ($field === 'structure_id' && ! $isCoded) {
            $label = optional(collect($lineage)->last())['name'] ?? $label;
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

    #[Computed]
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

        $this->componentForms[$rowKey]['name'] = $personnelModel->name;
        $this->componentForms[$rowKey]['surname'] = $personnelModel->surname;
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
                    default => '$' . $keyComponent
                };

                $_modified_component[$key][$_edit_key] = $this->attributeValuePayload($keyComponent, $valueComponent, $key);
            }
        }

        return $_modified_component;
    }

    private function fillCrudData(): array|\Livewire\Features\SupportEvents\Event
    {
        $this->runOrderValidation();
        $data = $this->prepareToCrud();
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

    private function prepareToCrud(): array
    {
        $message = '';
        $list_for_vacancy = [];

        switch ($this->selectedBlade) {
            case Order::BLADE_DEFAULT:
                $bladeData = $this->prepareDefaultBladeData();
                break;
            case Order::BLADE_BUSINESS_TRIP:
                $bladeData = $this->prepareBusinessTripBladeData();
                break;
            default:
                $bladeData = $this->prepareVacationBladeData();
        }

        [$list_for_vacancy, $message] = $this->resolveVacancyData($bladeData);

        return [
            'attributes' => $bladeData['attributes'] ?? [],
            'personnel_ids' => $bladeData['personnel_ids'] ?? [],
            'component_ids' => $bladeData['component_ids'] ?? [],
            'vacancy_list' => $list_for_vacancy,
            'message' => $message,
        ];
    }

    protected function runOrderValidation(): void
    {
        $rules = $this->validationRules();
        $this->validate($this->mergeValidationRules($rules['main'], $rules['dynamic']));
    }

    protected function mergeValidationRules(array ...$buckets): array
    {
        $merged = [];

        foreach ($buckets as $bucket) {
            foreach ($bucket as $key => $rule) {
                if ($rule === '' || $rule === null) {
                    continue;
                }
                $merged[$key] = $rule;
            }
        }

        return $merged;
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
