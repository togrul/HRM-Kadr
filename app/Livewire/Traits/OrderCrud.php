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
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Structure;
use App\Services\AttributeProcessService;
use App\Services\CheckVacancyService;
use App\Services\OrderCollectionListsService;
use App\Services\Orders\OrderLookupService;
use App\Services\WordSuffixService;
use Carbon\Carbon;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;

trait OrderCrud
{
    use BladeDataPreparation;
    use OrderValidationTrait;
    use SelectListTrait;
    use CallSwalTrait;

    protected OrderLookupService $orderLookupService;

    /**
     * Cheap in-memory cache for lookup datasets that rarely change during a component lifecycle.
     */
    protected array $staticLookups = [];

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

    public function bootOrderCrud(OrderLookupService $orderLookupService): void
    {
        $this->orderLookupService = $orderLookupService;
    }

    public function setStructure($id, $list, $field, $key, $isCoded)
    {
        $model = Structure::find($id);

        $models = Structure::find($model->getAllParentIds());

        $value = $this->buildStructureValue($models, $isCoded);

        $this->{$list}[$key][$field] = [
            'id' => $id,
            'name' => $value,
        ];
    }

    protected function buildStructureValue($models, $isCoded): string
    {
        $value = '';
        $suffixService = new WordSuffixService;

        foreach ($models as $parent) {
            $level_name = __(strtolower((collect(StructureEnum::cases())->pluck('name', 'value')[$parent->level])));

            $level_with_suffix = $parent->level > 1
                ? $suffixService->getMultiSuffix($level_name)
                : $suffixService->getStructureSuffix($level_name);

            $data = $isCoded
                ? $parent->code . $suffixService->getNumberSuffix($parent->code) . ' ' . $level_with_suffix . ' '
                : $suffixService->getStructureSuffix($parent->name) . ' ';

            $value .= $data;
        }

        return $value;
    }

    protected function cachedLookup(string $key, callable $resolver)
    {
        if (! array_key_exists($key, $this->staticLookups)) {
            $this->staticLookups[$key] = $resolver();
        }

        return $this->staticLookups[$key];
    }

    #[On('componentSelected')]
    public function componentSelected(?\App\Models\Component $value, $rowKey = null)
    {
        $this->selectedComponents[$rowKey] = explode(',', $value->dynamic_fields);
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

    #[On('dynamicSelectChanged')]
    public function dynamicSelectChanged($value, $field, $rowKey = null)
    {
        if ($field == 'personnel_id') {
            $this->updatePersonnelName($value, $rowKey);
        }

        $this->coded_list[$rowKey] = $field == 'structure_main_id' && $value == 1;
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
            $data[$arr] = [
                'id' => null,
                'name' => '---',
            ];
        }

        $this->components[] = $data;

        if (
            in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP]) &&
            ($this->componentRows > 0 && ! empty($this->components[0]['component_id']['id']))
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
                $_edit_key = match ($keyComponent) {
                    'rank_id' => '$rank',
                    'personnel_id' => '$fullname',
                    'structure_main_id' => '$structure_main',
                    'structure_id' => '$structure',
                    'position_id' => '$position',
                    'component_id', 'row' => $keyComponent,
                    default => '$' . $keyComponent
                };

                $_modified_component[$key][$_edit_key] = $keyComponent == 'component_id'
                    ? $valueComponent['id']
                    : $valueComponent;
            }
        }

        return $_modified_component;
    }

    private function modifyCodedList()
    {
        $this->coded_list = array_map(function ($value) {
            return $value === 1;
        }, collect($this->components)->pluck('structure_main_id.id')->toArray());
    }

    public function updatedComponents($value, $key)
    {
        $keyColumn = explode('.', $key)[0];
        if (! empty($this->components[$keyColumn]['start_date']) && ! empty($this->components[$keyColumn]['end_date'])) {
            $start_dt = Carbon::createFromDate($this->components[$keyColumn]['start_date']);
            $end_dt = Carbon::createFromDate($this->components[$keyColumn]['end_date']);
            $this->components[$keyColumn]['days'] = $start_dt->diffInDays($end_dt);
        }
    }

    private function fillCrudData(): array|\Livewire\Features\SupportEvents\Event
    {
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

    protected function formatOrderPersonnels(array $tabel_no_list, array $component_ids): array
    {
        $componentArray = array_pad($component_ids, count($tabel_no_list), end($component_ids));

        return array_map(function ($component_id) {
            return ['component_id' => $component_id];
        }, array_combine($tabel_no_list, $componentArray));
    }

    private function prepareToCrud(): array
    {
        $message = '';
        $list_for_vacancy = [];

        $this->validate($this->validationRules()['main']);
        $this->validate($this->validationRules()['dynamic']);

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
            if ($this->order['order_id'] == Order::IG_EMR) {
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

    private function attachComponents($orderModel, $_component_ids, $method)
    {
        foreach ($_component_ids as $key => $_component) {
            $component_data = ['row_number' => $key];
            if ($method == 'create') {
                $orderModel->components()->attach([
                    $_component => $component_data,
                ]);
            } else {
                $component_exists = $this->orderModelData->components()
                    ->where('row_number', $key)
                    ->where('component_id', $_component)
                    ->exists();

                if (! $component_exists) {
                    $orderModel->components()->attach([
                        $_component => $component_data,
                    ]);
                }
            }
        }
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

    public function render()
    {
        $this->modifyCodedList();

        $lookup = $this->resolveLookupCollections();

        $defaultCollections = [
            '_templates' => $lookup['templates'],
            '_components' => $lookup['components'],
            '_personnels' => $lookup['personnels'],
            '_ranks' => $lookup['ranks'],
            '_main_structures' => $lookup['main_structures'],
            '_structures' => $lookup['structures'],
            '_positions' => $lookup['positions'],
        ];

        $bladeCollections = (new OrderCollectionListsService(
            selectedBlade: $this->selectedBlade,
            personnel_name: $this->personnel_name,
            selected_personnel_list: $this->selected_personnel_list
        ))->handle();

        $viewName = ! empty($this->orderModel)
            ? 'livewire.orders.edit-order'
            : 'livewire.orders.add-order';

        return view($viewName, array_merge($defaultCollections, $bladeCollections));
    }

    protected function resolveLookupCollections(): array
    {
        $personnelIdList = array_filter(
            collect($this->components)->pluck('personnel_id.id')->toArray(),
            static fn ($value) => $value !== null
        );

        $isCandidateOrder = ($this->order['order_id'] ?? null) === Order::IG_EMR;

        return [
            'templates' => $this->orderLookupService->templates($this->selectedOrder ?? null, $this->searchTemplate),
            'components' => $this->orderLookupService->components($this->selectedTemplate),
            'personnels' => $this->orderLookupService->personnels($isCandidateOrder, $personnelIdList, $this->searchPersonnel),
            'ranks' => $this->cachedLookup('ranks', fn () => $this->orderLookupService->ranks()),
            'main_structures' => $this->cachedLookup('main_structures', fn () => $this->orderLookupService->mainStructures()),
            'structures' => $this->orderLookupService->structures($this->searchStructure),
            'positions' => $this->orderLookupService->positions($this->searchPosition),
        ];
    }
}
