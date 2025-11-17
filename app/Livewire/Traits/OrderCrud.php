<?php

namespace App\Livewire\Traits;

use App\Enums\StructureEnum;
use App\Helpers\UsefulHelpers;
use App\Livewire\Forms\Orders\OrderForm;
use App\Livewire\Forms\Orders\OrderSearchForm;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\Orders\DropdownLabelCache;
use App\Livewire\Traits\Orders\ManagesOrderComponents;
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
use App\Services\OrderCollectionListsService;
use App\Services\Orders\OrderAttributePersister;
use App\Services\Orders\OrderComponentPersister;
use App\Services\Orders\OrderLookupService;
use App\Services\Orders\OrderPersonnelPersister;
use App\Services\Orders\VacancyDiffService;
use App\Services\Orders\VacationCleanupService;
use App\Services\WordSuffixService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    protected OrderLookupService $orderLookupService;
    protected OrderComponentPersister $componentPersister;
    protected OrderPersonnelPersister $personnelPersister;

    /**
     * Cheap in-memory cache for lookup datasets that rarely change during a component lifecycle.
     */
    protected array $staticLookups = [];
    protected array $lookupCache = [];

    public OrderForm $orderForm;
    public OrderSearchForm $search;

    public string $title;

    //edited data model
    public $orderModel;

    //selected order category - from all order list
    public ?int $selectedOrder;

    //template secilende asagida komponentlerin gorunusu
    public $showComponent = false;

    //secilen sablon modelin ID si
    public $selectedTemplate;

    public array $vacancy_list;

    public array $originalComponents = [];

    public $personnel_name;

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
        $value = data_get($this->components[$row] ?? [], $field);

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
        $value = data_get($this->components[$row] ?? [], $field);

        if (is_array($value)) {
            return $value['id'] ?? null;
        }

        return $value;
    }

    protected function handleComponentPropertyMutation(string $propertyName, $value): bool
    {
        if (! str_starts_with($propertyName, 'components.')) {
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
            $this->components[$row][$field] = $value;
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
            $this->components[$row]['structure_id'] = null;
            unset($this->components[$row]['structure'], $this->components[$row]['structure_name']);
        }

        if (in_array($field, ['start_date', 'end_date'], true) && $row !== null) {
            if (! empty($this->components[$row]['start_date']) && ! empty($this->components[$row]['end_date'])) {
                $start_dt = Carbon::createFromDate($this->components[$row]['start_date']);
                $end_dt = Carbon::createFromDate($this->components[$row]['end_date']);
                $this->components[$row]['days'] = $start_dt->diffInDays($end_dt);
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

        $this->components[$rowKey]['name'] = $personnelModel->name;
        $this->components[$rowKey]['surname'] = $personnelModel->surname;
    }

    protected function modifyComponentList(array $components): array
    {
        $_modified_component = [];
        foreach ($components as $key => $component) {
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

    protected function fillPersonnelsToComponents(string $selectedBlade): array
    {
        $data = [];
        $_preparedArray = $this->selected_personnel_list;
        unset($_preparedArray['personnels']);
        $_finalArray = array_merge(...$_preparedArray);
        foreach ($_finalArray as $_keyFinal => $_final) {
            $row = $_final['row'];
            $componentRow = $this->components[$row];
            $columns = [
                'fullname' => $_final['fullname'],
                'rank' => $_final['rank'],
                'structure' => $_final['structure'],
                'start_date' => $componentRow['start_date'],
                'end_date' => $componentRow['end_date'],
                'component_id' => $componentRow['component_id'],
                'row' => $row,
                'position' => $_final['position'],
                'location' => $_final['location'] ?? '',
            ];

            switch ($selectedBlade) {
                case Order::BLADE_VACATION:
                    $columns['days'] = $componentRow['days'];
                    $columns['vacation_days_total'] = $_final['vacation_days_total'];
                    $columns['vacation_days_remaining'] = $columns['vacation_days_total'] - $columns['days'];
                    $columns['reserved_date_month'] = $_final['reserved_date_month'];
                    $columns['work_duration'] = $_final['work_duration'];
                    break;
                case Order::BLADE_BUSINESS_TRIP:
                    if ($this->selectedTemplate == PersonnelBusinessTrip::INTERNAL_BUSINESS_TRIP) {
                        $columns['meeting_hour'] = $componentRow['meeting_hour'];
                        $columns['return_month'] = $componentRow['return_month'];
                        $columns['return_day'] = $componentRow['return_day'];
                        $columns['transportation'] = $_final['transportation'] ?? [];
                        $columns['car'] = $_final['car'] ?? '';
                        $columns['weapon'] = $_final['weapon'];
                        $columns['bullet'] = $_final['bullet'] ?? 32;
                        $columns['service_dog'] = $_final['service_dog'] ?? false;
                    } else {
                        // structure_main_id
                        $columns['location'] = $componentRow['location'];
                    }
                    $columns['passport'] = $_final['passport'] ?? '';
                    break;
            }

            $data[$_keyFinal] = $columns;
        }

        return $data;
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

        if (! empty($bladeData) && $this->isCandidateOrder()) {
            $vacancyCandidates = match ($this->selectedBlade) {
                Order::BLADE_DEFAULT => $this->components,
                Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP => $this->selected_personnel_list,
            };

            $list_for_vacancy = (new VacancyDiffService)->diff($vacancyCandidates, $this->originalComponents);

            if ($this->selectedBlade === Order::BLADE_DEFAULT) {
                $normalizedVacancy = $this->normalizeVacancyEntries($list_for_vacancy);

                if (! empty($normalizedVacancy)) {
                    $vacancyCheck = (new CheckVacancyService)->handle($normalizedVacancy);
                    $this->vacancy_list = $vacancyCheck;
                    $message = $vacancyCheck['message'] ?? '';
                }
            }
        }

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
            $this->selected_personnel_list = [
                'personnels' => [],
            ];
        }
    }

    public function addToList(string $tabelno, int $row): void
    {
        $this->validate($this->validationRules()['dynamic']);
        $person = Personnel::with(['latestRank.rank', 'idDocuments', 'validPassport', 'structure', 'position', 'activeWeapons', 'activeWeapons.weapon'])
            ->where('tabel_no', $tabelno)
            ->first();

        $data = [
            'row' => $row,
            'key' => $tabelno,
            'rank' => $person->latestRank?->rank->name,
            'fullname' => $person->fullname,
        ];

        switch ($this->selectedBlade) {
            case Order::BLADE_VACATION:
                $person->load(['currentWork', 'yearlyVacation']);
                $data['vacation_days_total'] = $person->yearlyVacation[0]->vacation_days_total;
                $data['vacation_days_remaining'] = $person->yearlyVacation[0]->remaining_days;
                $data['reserved_date_month'] = array_search($person->yearlyVacation[0]->reserved_date_month, UsefulHelpers::monthsList(config('app.locale')));
                $workDuration = $person->currentWork?->join_date->diffInMonths(Carbon::now());
                $data['work_duration'] = $workDuration;
                if (
                    $data['vacation_days_remaining'] < 1
                    || (array_key_exists('days', $this->components[$row]) && $this->components[$row]['days'] > $data['vacation_days_remaining'])
                ) {
                    $this->dispatch('checkVacationAdd', __('There are not enough days left for this vacation.'));
                    return;
                }
                if ($workDuration < 6) {
                    $this->dispatch('addError', $data['fullname'] . ' 6 aydan az müddətdir işləyir.');
                }
                $data['position'] = $person->position->name;
                $data['structure'] = $person->structure->name;
                break;
            case Order::BLADE_BUSINESS_TRIP:
                if ($this->selectedTemplate == PersonnelBusinessTrip::INTERNAL_BUSINESS_TRIP) {
                    $personWeapons = collect($person->activeWeapons)
                        ->map(fn($activeWeapon) => "{$activeWeapon->weapon->name} №_{$activeWeapon->weapon_serial}")
                        ->implode(' ');
                    $data['passport'] = $person->idDocuments->serialNumber ?? '';
                    $data['weapon'] = $personWeapons;
                } else {
                    $data['passport'] = $person->validPassport->serial_number ?? '';
                }
                $data['position'] = $person->position->name;
                $data['structure'] = $this->getStructureFull($person->structure);
                break;
        }

        $this->selected_personnel_list[$row][] = $data;

        $this->selected_personnel_list['personnels'][] = $tabelno;

        $this->reset('personnel_name');
    }

    protected function getStructureFull($structure)
    {
        $structureName = $structure?->topLevelParent() ?? $structure->name;
        $suffixService = new WordSuffixService;

        $levels = array_column(StructureEnum::cases(), 'name', 'value');
        $levelName = __(strtolower($levels[$structure->level]) ?? '');

        return is_numeric($structureName)
            ? "{$structureName}{$suffixService->getNumberSuffix((int)$structureName)} {$levelName}"
            : $structureName;
    }

    public function removeFromList($_currentRow, $_mainRow): void
    {
        $tabel = $this->selected_personnel_list[$_mainRow][$_currentRow]['key'];
        $tabel_row = array_search($tabel, $this->selected_personnel_list['personnels']);

        unset($this->selected_personnel_list[$_mainRow][$_currentRow]);
        unset($this->selected_personnel_list['personnels'][$tabel_row]);
    }

    private function saveAttribute($orderModel, $_attributes, $method): void
    {
        (new OrderAttributePersister)->persist($orderModel, $_attributes, $method);

        if ($method == 'update' && in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP])) {
            (new VacationCleanupService)->handle(
                $orderModel,
                collect($_attributes),
                $this->selectedBlade,
                $this->selected_personnel_list['personnels']
            );
        }
    }

    #[Isolate]
    public function getStatusesProperty()
    {
        return OrderStatus::where('locale', config('app.locale'))->get();
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

        $rankOptions = $this->optionsFromCollection($lookup['ranks'], fn ($rank) => trim((string) $rank->name));
        $this->registerComponentOptionLabels('rank_id', $rankOptions);

        $personnelOptions = $this->optionsFromCollection($lookup['personnels'], fn ($person) => $this->personnelOptionLabel($person));
        $this->registerComponentOptionLabels('personnel_id', $personnelOptions);

        $mainStructureOptions = $this->optionsFromCollection($lookup['main_structures'], fn ($structure) => trim((string) $structure->name));
        $this->registerComponentOptionLabels('structure_main_id', $mainStructureOptions);

        $positionOptions = $this->optionsFromCollection($lookup['positions'], fn ($position) => trim((string) $position->name));
        $this->registerComponentOptionLabels('position_id', $positionOptions);

        $defaultCollections = [
            '_templates' => $lookup['templates'],
            '_components' => $lookup['components'],
            '_personnels' => $personnelOptions,
            '_ranks' => $rankOptions,
            '_main_structures' => $mainStructureOptions,
            '_structures' => $lookup['structures'],
            '_positions' => $positionOptions,
        ];

        $bladeCollections = (new OrderCollectionListsService(
            selectedBlade: $this->selectedBlade,
            personnel_name: $this->personnel_name,
            selected_personnel_list: $this->selected_personnel_list
        ))->handle();

        if (isset($bladeCollections['_transportations'])) {
            $transportOptions = collect($bladeCollections['_transportations'])
                ->map(fn ($item) => [
                    'id' => $item['id'],
                    'label' => $item['name'],
                ])
                ->values()
                ->all();
            $bladeCollections['_transportations'] = $transportOptions;
            $this->registerComponentOptionLabels('transportation', $transportOptions);
        }

        $viewName = ! empty($this->orderModel)
            ? 'livewire.orders.edit-order'
            : 'livewire.orders.add-order';

        return view($viewName, array_merge($defaultCollections, $bladeCollections));
    }

    protected function syncSelectedComponentsFromLookup(): void
    {
        foreach ($this->components as $row => $component) {
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
            collect($this->components)->pluck('personnel_id')->toArray(),
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

        $components = $this->memoizedLookup(
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
            'components' => $components,
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

        $this->rememberComponentDefinitions($components);

        return $lookups;
    }

    protected function rememberComponentDefinitions(Collection $components): void
    {
        $this->componentDefinitions = $components
            ->mapWithKeys(fn ($component) => [
                (int) $component->id => [
                    'id' => (int) $component->id,
                    'dynamic_fields' => $component->dynamic_fields,
                ],
            ])
            ->all();
    }
}
