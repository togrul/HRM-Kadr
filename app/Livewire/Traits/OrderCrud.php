<?php

namespace App\Livewire\Traits;

use App\Enums\StructureEnum;
use App\Helpers\UsefulHelpers;
use App\Livewire\Traits\Admin\CallSwalTrait;
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
use App\Models\Structure;
use App\Services\AttributeProcessService;
use App\Services\CheckVacancyService;
use App\Services\OrderCollectionListsService;
use App\Services\Orders\OrderComponentPersister;
use App\Services\Orders\OrderLookupService;
use App\Services\Orders\OrderPersonnelPersister;
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

    protected OrderLookupService $orderLookupService;
    protected OrderComponentPersister $componentPersister;
    protected OrderPersonnelPersister $personnelPersister;

    /**
     * Cheap in-memory cache for lookup datasets that rarely change during a component lifecycle.
     */
    protected array $staticLookups = [];
    protected array $lookupCache = [];

    //esas cedvelin listi
    public array $order = [];

    public $searchTemplate;

    public $searchPersonnel;

    public $searchStructure;

    public $searchPosition;

    public string $title;

    //edited data model
    public $orderModel;

    //selected order category - from all order list
    public ?int $selectedOrder;

    //template secilende asagida komponentlerin gorunusu
    public $showComponent = false;

    //dinamik componentlerin secildiyi list
    public array $components = [];

    //add row hissede dinamik listin generasiya olunmasi
    public $componentRows;

    //secilen sablon modelin ID si
    public $selectedTemplate;

    //secilen component - dinamik fieldleri generasiya elemek ucun
    public array $selectedComponents = [];

    public array $coded_list;

    public array $vacancy_list;

    public array $originalComponents = [];

    public $personnel_name;

    public $selected_personnel_list = [];

    public $selectedBlade;

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

    protected array $componentDropdownFields = [
        'rank_id',
        'personnel_id',
        'structure_main_id',
        'structure_id',
        'position_id',
        'transportation',
    ];

    public array $componentOptionLabels = [];

    protected function isDropdownField(string $field): bool
    {
        return in_array($field, $this->componentDropdownFields, true);
    }

    protected function registerComponentOptionLabels(string $field, array $options): void
    {
        foreach ($options as $option) {
            $this->componentOptionLabels[$field][(int) $option['id']] = (string) $option['label'];
        }
    }

    protected function dropdownFieldLabel(string $field, $value, ?int $row = null): string
    {
        if (empty($value)) {
            return '---';
        }

        $value = (int) $value;

        if (isset($this->componentOptionLabels[$field][$value])) {
            return $this->componentOptionLabels[$field][$value];
        }

        $label = match ($field) {
            'structure_main_id' => $this->resolveStructureLabel($value, true),
            'structure_id' => $this->structureLabelForRow($row, $value),
            default => (string) $value,
        };

        $this->componentOptionLabels[$field][$value] = $label;

        return $label;
    }

    protected function resolveStructureLabel(int $id, bool $isCoded): string
    {
        $lineage = $this->structureLineage($id);

        if (empty($lineage)) {
            return '---';
        }

        return $this->buildStructureValue($lineage, $isCoded);
    }

    protected function formatDropdownValue(string $field, $value, ?int $row = null): array
    {
        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        $id = $value !== null ? (int) $value : null;

        return [
            'id' => $id,
            'name' => $id ? $this->dropdownFieldLabel($field, $id, $row) : '---',
        ];
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

    /**
     * Cached lookup for all structures (id => attributes) to avoid per-click queries.
     *
     * @var \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected $structureLookup;
    protected array $structureLineageCache = [];

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

    protected function buildStructureValue(array $lineage, $isCoded): string
    {
        $value = '';
        $suffixService = new WordSuffixService;

        foreach ($lineage as $parent) {
            $level_name = __(strtolower((collect(StructureEnum::cases())->pluck('name', 'value')[$parent['level']])));

            $level_with_suffix = $parent['level'] > 1
                ? $suffixService->getMultiSuffix($level_name)
                : $suffixService->getStructureSuffix($level_name);

            $data = $isCoded
                ? $parent['code'] . $suffixService->getNumberSuffix($parent['code']) . ' ' . $level_with_suffix . ' '
                : $suffixService->getStructureSuffix($parent['name']) . ' ';

            $value .= $data;
        }

        return $value;
    }

    protected function structureLineage(int $structureId): array
    {
        if (isset($this->structureLineageCache[$structureId])) {
            return $this->structureLineageCache[$structureId];
        }

        $index = $this->structureIndex();

        $nodes = [];
        $currentId = $structureId;

        while ($currentId && ($node = $index->get($currentId))) {
            array_unshift($nodes, $node);
            $currentId = $node['parent_id'] ?? null;
        }

        return $this->structureLineageCache[$structureId] = $nodes;
    }

    protected function structureIndex(): \Illuminate\Support\Collection
    {
        if ($this->structureLookup instanceof \Illuminate\Support\Collection) {
            return $this->structureLookup;
        }

        $this->structureLookup = Structure::query()
            ->select('id', 'parent_id', 'name', 'code', 'level')
            ->get()
            ->map(fn ($structure) => [
                'id' => (int) $structure->id,
                'parent_id' => $structure->parent_id ? (int) $structure->parent_id : null,
                'name' => $structure->name,
                'code' => $structure->code,
                'level' => (int) $structure->level,
            ])
            ->keyBy('id');

        return $this->structureLookup;
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

        $component = Component::find($componentId);
        $this->selectedComponents[$rowKey] = $component
            ? explode(',', (string) $component->dynamic_fields)
            : [];
    }

    #[On('templateSelected')]
    public function templateSelected($value)
    {
        $this->showComponent = $value > 0;
        $this->selectedTemplate = $value;
        $this->resetFields();
        if (empty($this->selectedOrder)) {
            $order = OrderType::with('order')
                ->where('id', $value)
                ->first();

            if ($order) {
                $this->order['order_id'] = $order->order_id;
                $this->selectedBlade = $order->order->blade;
            }
        }
        $this->reset('selectedComponents');
        $this->fillEmptyComponent();
    }

    public function updatedOrderOrderTypeId($value)
    {
        $this->templateSelected((int) $value);
    }

    #[Computed]
    public function templateOptions(): array
    {
        $collection = $this->orderLookupService
            ->templates($this->selectedOrder ?? null, $this->searchTemplate)
            ->map(fn ($template) => [
                'id' => $template->id,
                'label' => trim((string) $template->name),
            ]);

        $selected = $this->order['order_type_id'] ?? null;
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
        $personnelModel = $this->order['order_id'] == Order::IG_EMR
            ? Candidate::find($value)
            : Personnel::find($value);

        $this->components[$rowKey]['name'] = $personnelModel->name;
        $this->components[$rowKey]['surname'] = $personnelModel->surname;
    }

    protected function resetFields()
    {
        $this->components = [];
        $this->componentRows = 1;
    }

    protected function fillEmptyComponent()
    {
        $list = match ($this->selectedBlade) {
            Order::BLADE_VACATION => ['component_id'],
            Order::BLADE_DEFAULT => ['rank_id', 'component_id', 'personnel_id', 'structure_main_id', 'structure_id', 'position_id'],
            Order::BLADE_BUSINESS_TRIP => ['component_id']
        };

        $this->generateFilledArray($list);

        $this->coded_list[] = false;
    }

    protected function generateFilledArray(array $array)
    {
        $data = [];
        foreach ($array as $arr) {
            if ($arr === 'component_id') {
                $data[$arr] = null;
                continue;
            }

            if ($this->isDropdownField($arr)) {
                $data[$arr] = null;
                continue;
            }

            $data[$arr] = '';
        }

        $this->components[] = $data;

        if (
            in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP]) &&
            ($this->componentRows > 0 && ! empty($this->components[0]['component_id']))
        ) {
            $this->components[$this->componentRows]['component_id'] = $this->components[0]['component_id'];
        }
    }

    public function addRow()
    {
        $this->fillEmptyComponent();

        $this->componentRows++;
    }

    public function deleteRow()
    {
        if ($this->componentRows > 1) {
            unset($this->components[$this->componentRows - 1]);
            unset($this->selectedComponents[$this->componentRows - 1]);
            $this->resetValidation();
            $this->componentRows--;
        }
    }

    protected function modifyComponentList(array $components): array
    {
        $_modified_component = [];
        foreach ($components as $key => $component) {
            foreach ($component as $keyComponent => $valueComponent) {
                if ($this->isDropdownField($keyComponent)) {
                    $valueComponent = $this->formatDropdownValue($keyComponent, $valueComponent, $key);
                }

                $_edit_key = match ($keyComponent) {
                    'rank_id' => '$rank',
                    'personnel_id' => '$fullname',
                    'structure_main_id' => '$structure_main',
                    'structure_id' => '$structure',
                    'position_id' => '$position',
                    'component_id', 'row' => $keyComponent,
                    default => '$' . $keyComponent
                };

                $_modified_component[$key][$_edit_key] = $valueComponent;
            }
        }

        return $_modified_component;
    }

    private function modifyCodedList()
    {
        $this->coded_list = array_map(function ($value) {
            $id = is_array($value) ? ($value['id'] ?? null) : $value;
            return (int) $id === 1;
        }, collect($this->components)->pluck('structure_main_id')->toArray());
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

        if (! empty($bladeData)) {
            $_sentList = match ($this->selectedBlade) {
                Order::BLADE_DEFAULT => $this->components,
                Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP => $this->selected_personnel_list,
            };
            $list_for_vacancy = $this->prepareListForVacancy($_sentList, $this->originalComponents);
            if ($this->isCandidateOrder()) {
                $this->vacancy_list = (new CheckVacancyService)->handle($list_for_vacancy);
                $message = ! empty($this->vacancy_list) ? $this->vacancy_list['message'] : '';
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
        $this->validate($rules['main']);
        $this->validate($rules['dynamic']);
    }

    private function prepareListForVacancy($list, $originalList)
    {
        return ! empty($originalList)
            ? UsefulHelpers::compareMultidimensionalArrays($list, $originalList)
            : $list;
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
            $this->order['given_by'] = cache('settings')['Chief'];
            $this->order['given_by_rank'] = cache('settings')['Chief rank'];
            $this->order['order_id'] = $this->selectedOrder;
            $this->componentRows = 1;
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
        $orderNo = $method == 'create' ? $this->order['order_no'] : $this->orderModelData->order_no;
        foreach ($_attributes as $index => $_attr) {
            (new AttributeProcessService(
                orderModel: $orderModel,
                attributeData: $_attr,
                method: $method,
                index: $index,
                orderNo: $orderNo
            ))
                ->process();
        }
        if ($method == 'update' && in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP])) {
            $this->handleVacationBlade($orderModel, $_attributes, $this->selectedBlade);
        }
    }

    private function handleVacationBlade($orderModel, $attributes, $blade)
    {
        $deletedPersonnels = array_diff(
            $this->orderModelData->personnels->pluck('tabel_no')->all(),
            $this->selected_personnel_list['personnels']
        );

        $currentFullNames = collect($attributes)->pluck('$fullname');

        // Step 3: Delete records that are not in the new data
        $orderModel->attributes->each(function ($record) use ($currentFullNames) {
            if (! $currentFullNames->contains($record->attributes['$fullname']['value'])) {
                $record->delete();
            }
        });

        switch ($blade) {
            case Order::BLADE_VACATION:
                PersonnelVacation::whereIn('tabel_no', $deletedPersonnels)
                    ->where('order_no', $orderModel->order_no)
                    ->delete();
                break;
            case Order::BLADE_BUSINESS_TRIP:
                PersonnelBusinessTrip::whereIn('tabel_no', $deletedPersonnels)
                    ->where('order_no', $orderModel->order_no)
                    ->delete();
                break;
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
        return ($this->order['order_id'] ?? null) == Order::IG_EMR;
    }

    public function render()
    {
        $this->modifyCodedList();

        $lookup = $this->resolveLookupCollections();
        $this->syncSelectedComponentsFromLookup($lookup['components']);

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

    protected function syncSelectedComponentsFromLookup(Collection $components): void
    {
        foreach ($this->components as $row => $component) {
            $componentId = $component['component_id'] ?? null;
            if (empty($componentId)) {
                $this->selectedComponents[$row] = [];
                continue;
            }

            $selected = $components->firstWhere('id', (int) $componentId);
            $this->selectedComponents[$row] = $selected
                ? array_filter(explode(',', (string) $selected->dynamic_fields))
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
        $searchTemplate = $this->searchTemplate ?? '';
        $searchPersonnel = $this->searchPersonnel ?? '';
        $searchStructure = $this->searchStructure ?? '';
        $searchPosition = $this->searchPosition ?? '';
        $needsPersonnelLookup = $this->selectedBlade === Order::BLADE_DEFAULT;

        return [
            'templates' => $this->memoizedLookup(
                'templates',
                [$selectedOrder, $searchTemplate],
                fn () => $this->orderLookupService->templates($selectedOrder, $searchTemplate)
            ),
            'components' => $this->memoizedLookup(
                'components',
                [$selectedTemplate],
                fn () => $this->orderLookupService->components($selectedTemplate)
            ),
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
    }
}
