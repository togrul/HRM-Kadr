<?php

namespace App\Livewire\Traits;

use App\Enums\StructureEnum;
use App\Helpers\UsefulHelpers;
use App\Models\Candidate;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Structure;
use App\Services\CheckVacancyService;
use App\Services\WordSuffixService;
use Carbon\Carbon;
use Livewire\Attributes\On;

trait OrderCrud
{
    use SelectListTrait;

    //esas cedvelin listi
    public array $order = [];
    public $searchTemplate,$searchPersonnel,$searchStructure,$searchPosition;
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

    public function validationRules()
    {
        return [
            'main' => [
                'order.order_type_id.id' => 'required|int|exists:order_types,id',
                'order.order_no' => 'required|min:3|unique:order_logs,order_no'. (!empty($this->orderModel) ? ','.$this->orderModelData->id : ''),
                'order.given_date' => 'required',
                'order.status_id' => 'required|exists:order_statuses,id'
            ],
            'dynamic' => [
                'components.*.component_id.id' => 'required|int|exists:components,id',
                'components.*.rank_id.id' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'nullable|int|exists:ranks,id' : '',
                'components.*.personnel_id.id' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|int' : '',
                'components.*.day' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required' : '',
                'components.*.month' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|string|min:1' : '',
                'components.*.year' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required' : '',
                'components.*.structure_main_id.id' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|int|exists:structures,id' : '',
                'components.*.structure_id.id' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|int|exists:structures,id' : '',
                'components.*.position_id.id' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|int|exists:positions,id' : '',
                'components.*.name' =>  $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|string' : '',
                'components.*.surname' =>  $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|string' : '',
                'components.*.start_date' => $this->selectedBlade == Order::BLADE_VACATION ? 'required|date' : '',
                'components.*.end_date' => $this->selectedBlade == Order::BLADE_VACATION ? 'required|date|after:start_date' : '',
                'components.*.days' => $this->selectedBlade == Order::BLADE_VACATION ? 'required|int|min:0' : ''
            ]
        ];
    }

    protected function validationAttributes()
    {
        return [
            'order.order_type_id.id' => __('Template'),
            'order.order_no' => __('Order number'),
            'order.given_date' => __('Given date'),
            'order.status_id' => __('Status'),
            'components.*.component_id.id' => __('Component'),
            'components.*.personnel_id.id' => __('Personnel'),
            'components.*.rank_id.id' => __('Rank'),
            'components.*.day' => __('Day'),
            'components.*.month' => __('Month'),
            'components.*.year' => __('Year'),
            'components.*.structure_main_id.id' => __('Main structure'),
            'components.*.structure_id.id' => __('Structure'),
            'components.*.position_id.id' => __('Position'),
            'components.*.name' => __('Name'),
            'components.*.surname' => __('Surname'),
            'components.*.start_date' => __('Start Date'),
            'components.*.end_date' => __('End Date'),
            'components.*.days' => __('Days'),
        ];
    }

    public function setStructure($id,$list,$field,$key,$isCoded)
    {
        $model = Structure::find($id);

        $models = Structure::find($model->getAllParentIds());

        $value = $this->buildStructureValue($models, $isCoded);

        $this->{$list}[$key][$field] = [
                'id' => $id,
                'name' => $value
        ];
    }

    protected function buildStructureValue($models, $isCoded)
    {
        $value = "";
        $suffixService = new WordSuffixService();

        foreach ($models as $parent) {
            $level_name = __(strtolower((collect(StructureEnum::cases())->pluck('name','value')[$parent->level])));

            $level_with_suffix = $parent->level > 1
                ? $suffixService->getMultiSuffix($level_name)
                : $suffixService->getStructureSuffix($level_name);

            $data = $isCoded
                ? $parent->code. $suffixService->getNumberSuffix($parent->code) . " " . $level_with_suffix . " "
                : $suffixService->getStructureSuffix($parent->name) . " ";

            $value .= $data;
        }

        return $value;
    }

    #[On('componentSelected')]
    public function componentSelected(?\App\Models\Component $value, $rowKey = null)
    {
        $this->selectedComponents[$rowKey] = explode(',',$value->dynamic_fields);
    }

    #[On('templateSelected')]
    public function templateSelected($value)
    {
        $this->showComponent = $value > 0;
        $this->selectedTemplate = $value;
        $this->resetFields();
        if(empty($this->selectedOrder))
        {
            $order = OrderType::with('order')
                        ->where('id',$value)
                        ->first();

            if($order)
            {
                $this->order['order_id'] = $order->order_id;
                $this->selectedBlade = $order->order->blade;
            }
        }
        $this->fillEmptyComponent();
    }

    #[On('dynamicSelectChanged')]
    public function dynamicSelectChanged($value,$field,$rowKey = null)
    {
        if($field == 'personnel_id')
        {
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
        $list = $this->selectedBlade == Order::BLADE_VACATION
                ? ['component_id']
                : ['rank_id','component_id','personnel_id','structure_main_id','structure_id','position_id'];

        $this->generateFilledArray($list);

        $this->coded_list[] = false;
    }

    protected function generateFilledArray(array $array)
    {
        $data = [];
        foreach ($array as $arr)
        {
            $data[$arr] = [
                'id' => null,
                'name' => '---'
            ];
        }
        array_push($this->components,$data);

        if($this->selectedBlade == Order::BLADE_VACATION && ($this->componentRows > 0 && !empty($this->components[0]['component_id']['id'])))
        {
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
        if($this->componentRows > 1)
        {
            unset($this->components[$this->componentRows - 1]);
            unset($this->selectedComponents[$this->componentRows - 1]);
            $this->resetValidation();
            $this->componentRows--;
        }
    }

    protected function modifyComponentList(array $components)
    {
        $_modified_component = [];
        foreach ($components as $key => $component)
        {
            foreach ($component as $keyComponent => $valueComponent)
            {
                $_edit_key = match ($keyComponent)
                {
                    'rank_id' => '$rank',
                    'personnel_id' => '$fullname',
                    'structure_main_id' => '$structure_main',
                    'structure_id' => '$structure',
                    'position_id' => '$position',
                    'component_id','row' => $keyComponent,
                    default => "$".$keyComponent
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
        $this->coded_list = array_map(function ($value){
            return $value === 1;
        },collect($this->components)->pluck('structure_main_id.id')->toArray());
    }

    public function updatedComponents($value,$key)
    {
        $keyColumn = explode('.',$key)[0];
        if (!empty($this->components[$keyColumn]['start_date']) && !empty($this->components[$keyColumn]['end_date'])) {
            $start_dt = Carbon::createFromDate($this->components[$keyColumn]['start_date']);
            $end_dt = Carbon::createFromDate($this->components[$keyColumn]['end_date']);
            $this->components[$keyColumn]['days'] = $start_dt->diffInDays($end_dt);
        }
    }

    private function fillCrudData()
    {
        $data = $this->prepareToCrud();
        $message = $data['message'];

        if(!empty($message))
        {
            return $this->dispatch('checkVacancyWasSet',$message);
        }

       return [
           'attributes' => $data['attributes'],
           'personnel_ids' => $data['personnel_ids'],
           'component_ids' => $data['component_ids'],
           'vacancy_list' => $data['vacancy_list'],
       ];
    }

    protected function fillVacationPersonnelsToComponents() : array
    {
        $data = [];
        $_preparedArray = $this->selected_personnel_list;
        unset($_preparedArray['personnels']);
        $_finalArray = array_merge(...$_preparedArray);
        foreach ($_finalArray as $_keyFinal => $_final)
        {
            $row = $_final['row'];
            $data[$_keyFinal] = [
                'fullname' => $_final['fullname'],
                'rank' => $_final['rank'],
                'structure' => $_final['structure'],
                'position' => $_final['position'],
                'start_date' => $this->components[$row]['start_date'],
                'end_date' => $this->components[$row]['end_date'],
                'days' => $this->components[$row]['days'],
                'location' => $_final['location'] ?? '',
                'component_id' => $this->components[$row]['component_id'],
                'row' => $row
            ];
        }

        return $data;
    }

    protected function formatOrderPersonnels(array $tabel_no_list, array $component_ids)
    {
        return array_map(function ($component_id) {
            return ['component_id' => $component_id];
        }, array_combine($tabel_no_list, $component_ids));
    }

    private function prepareToCrud() : array
    {
        if($this->selectedBlade == Order::BLADE_DEFAULT)
        {
            $_attrData = $this->components;

            $_personnel_ids_list = collect($this->components)->pluck('personnel_id.id')->toArray();
            $_personnel_ids = Personnel::find($_personnel_ids_list)->pluck('tabel_no')->toArray();
            $_component_ids = collect($_attrData)->pluck('component_id.id')->toArray();
        }
        else
        {
            $_attrData = $this->fillVacationPersonnelsToComponents();
            $_personnel_ids = $this->selected_personnel_list['personnels'];
            $_component_ids = collect($_attrData)
                    ->unique('days')
                    ->values()
                    ->pluck('component_id.id')
                    ->all();
        }
        $_attributes = $this->modifyComponentList($_attrData);

        $this->validate($this->validationRules()['main']);
        $this->validate($this->validationRules()['dynamic']);

        $list_for_vacancy = [];
        $message = '';
        if($this->order['order_id'] == Order::IG_EMR)
        {
            /** secilen vezifelerin bos olub olmadigin yoxlayir yoxdursa vakansiya yaratmaga imkan verir **/
            $list_for_vacancy = !empty($this->originalComponents)
                ? UsefulHelpers::compareMultidimensionalArrays($this->components,$this->originalComponents)
                : $this->components;

            $this->vacancy_list = resolve(CheckVacancyService::class)->handle($list_for_vacancy);
            $message = !empty($this->vacancy_list) ? $this->vacancy_list['message']  : '';
        }
        elseif($this->selectedBlade == Order::BLADE_VACATION)
        {
            $list_for_vacancy = !empty($this->originalComponents)
                ? UsefulHelpers::compareMultidimensionalArrays($this->selected_personnel_list,$this->originalComponents)
                : $this->selected_personnel_list;
        }

        return [
            'attributes' => $_attributes,
            'personnel_ids' => $_personnel_ids,
            'component_ids' => $_component_ids,
            'vacancy_list' => $list_for_vacancy,
            'message' => $message
        ];
    }

    public function mount()
    {
        if(!empty($this->orderModel))
        {
            $this->title = __('Edit order');
            $this->fillOrder();
        }
        else
        {
            $this->title = __('Add order');
            $this->order['given_by'] = cache('settings')['Chief'];
            $this->order['given_by_rank'] = cache('settings')['Chief rank'];
            $this->order['order_id'] = $this->selectedOrder;
            $this->componentRows = 1;
            $this->selected_personnel_list = [
                'personnels' => []
            ];
        }
    }

    private function getPersonnelsStatusReady($_personnel_id_list)
    {
        return Candidate::when(!empty($this->searchPersonnel), function ($q) {
            $q->where(function ($query) {
                $query->where('name', 'LIKE', "%{$this->searchPersonnel}%")
                    ->orWhere('surname', 'LIKE', "%{$this->searchPersonnel}%");
            });
        })
            ->whereNotIn('id', $_personnel_id_list)
            ->where('status_id', 30)
            ->get();
    }

    private function getPersonnelsList($_personnel_id_list)
    {
        return Personnel::when(!empty($this->searchPersonnel), function ($q) {
            $q->where(function ($query) {
                $query->where('name', 'LIKE', "%{$this->searchPersonnel}%")
                    ->orWhere('surname', 'LIKE', "%{$this->searchPersonnel}%");
            });
        })
            ->whereNotIn('id', $_personnel_id_list)
            ->whereNull('leave_work_date')
            ->orderBy('position_id')
            ->orderBy('structure_id')
            ->get();
    }

    public function addToList(string $tabelno,int $row)
    {
        $person = Personnel::with(['latestRank.rank','structure','position'])
                            ->where('tabel_no', $tabelno)
                            ->first();

        $this->selected_personnel_list[$row][] = [
            'row' => $row,
            'key' => $tabelno,
            'rank' => $person->latestRank?->rank->name,
            'fullname' => $person->fullname,
            'structure' => $person->structure->name,
            'position' => $person->position->name
        ];
        $this->selected_personnel_list['personnels'][] = $tabelno;

        $this->reset('personnel_name');
    }

    public function removeFromList($_currentRow, $_mainRow)
    {
        $tabel = $this->selected_personnel_list[$_mainRow][$_currentRow]['key'];
        $tabel_row = array_search($tabel,$this->selected_personnel_list['personnels']);

        unset($this->selected_personnel_list[$_mainRow][$_currentRow]);
        unset($this->selected_personnel_list['personnels'][$tabel_row]);
    }

    private function attachComponents($orderModel,$_component_ids,$method)
    {
        foreach($_component_ids as $key => $_component) {
            if($method == 'create')
            {
                $orderModel->components()->attach([
                    $_component => ['row_number' => $key]
                ]);
            }
            else
            {
                $component_exists = $this->orderModelData->components()
                    ->where('row_number' , $key)
                    ->where('component_id', $_component)
                    ->first();

                if(!$component_exists) {
                    $orderModel->components()->attach([
                        $_component => ['row_number' => $key]
                    ]);
                }
            }
        }
    }

    private function saveAttribute($orderModel,$_attributes,$method)
    {
        foreach ($_attributes as $k => $_attr)
        {
            $component_id = $_attr['component_id'];
            $row = $_attr['row'] ?? $k;
            unset($_attr['component_id']);

            $attr_data = [];
            foreach ($_attr as $key => $value) {
                $attr_data[$key] = [
                    'id' =>  is_array($value) ? $value['id'] : null,
                    'value' => is_array($value) ? $value['name'] : $value
                ];
            }

            if($method == 'create')
            {
                $orderModel->attributes()->create([
                    'component_id' => $component_id,
                    'attributes' => $attr_data,
                    'row_number' => $row
                ]);
            }
            else
            {
                $orderModel->attributes()->updateOrCreate(
                    [
                        'component_id' => $component_id,
                        'order_no' => $this->orderModelData->order_no,
                        'row_number' => $row,
                        'attributes->$fullname->value' => $attr_data['$fullname']['value']
                    ],
                    [
                        'attributes' => $attr_data
                    ]
                );
            }
        }
        if($method == 'update' && $this->selectedBlade == Order::BLADE_VACATION)
        {
            $deletedPersonnels = array_diff(
                $this->orderModelData->personnels->pluck('tabel_no')->all(),
                $this->selected_personnel_list['personnels']
            );

            $currentRecords = collect($_attributes)->pluck('$fullname');
            // Step 3: Delete records that are not in the new data
            $orderModel->attributes->each(function ($record) use ($currentRecords) {
                if(!$currentRecords->contains($record->attributes['$fullname']['value'])) {
                    $record->delete();
                }
            });

            PersonnelVacation::whereIn('tabel_no',$deletedPersonnels)
                                ->where('order_no',$orderModel->order_no)
                                ->delete();
        }
    }

    public function render()
    {
        $this->modifyCodedList();
        // template secimnidede type i secmek cunki order_id onsuzda qiraqdan gelir auto bilinir
        // duzeltmek lazimdir.yalniz 1 id var ve onlari ortaq eden hecne yoxdur.
        // db duzelishler
        $_templates = OrderType::when(!empty($this->searchTemplate),function ($q){
            $q->where('name','LIKE',"%{$this->searchTemplate}%");
        })
            ->when(!empty($this->selectedOrder),function ($q){
                $q->where('order_id',$this->selectedOrder);
            })
            ->get();

        $_components = \App\Models\Component::with('orderType')
            ->where('order_type_id',$this->selectedTemplate)
            ->get();

        $_personnel_id_list = array_filter(
            collect($this->components)->pluck('personnel_id.id')->toArray(), function ($value)
            {
                return $value !== null;
            }
        );

        $_personnels = array_key_exists('order_id',$this->order) && $this->order['order_id'] == Order::IG_EMR
                        ? $this->getPersonnelsStatusReady($_personnel_id_list)
                        : $this->getPersonnelsList($_personnel_id_list);


        $_ranks = Rank::where('is_active',true)->get();

        $_main_structures = Structure::where('code',0)->orderBy('id')->get();
        $_structures = Structure::with('subs')
                            ->when(!empty($this->searchStructure),function ($q){
                                $q->where('name','LIKE',"%{$this->searchStructure}%");
                            })
                                ->whereNotNull('parent_id')
                                ->where('code','<>',0)
                                ->orderBy('code')
                                ->get();

        $_positions = Position::when(!empty($this->searchPosition),function ($q){
            $q->where('name','LIKE',"%{$this->searchPosition}%");
        })->get();

        $_statuses = OrderStatus::where('locale',config('app.locale'))->get();

        $_personnel_list_by_name = strlen($this->personnel_name) > 2
            ? Personnel::with('inActiveVacation')
                ->when(!empty($this->personnel_name)  ,function ($q){
                $q->where(function ($query) {
                    $query->where('name', 'LIKE', "%{$this->personnel_name}%")
                        ->orWhere('surname', 'LIKE', "%{$this->personnel_name}%");
                });
            })
                ->active()
                ->where('deleted_at',NULL)
                ->whereNotIn('tabel_no',$this->selected_personnel_list['personnels'])
                ->get()
            : [];

        $view_name = !empty($this->orderModel)
                    ? 'livewire.orders.edit-order'
                    : 'livewire.orders.add-order';

        return view($view_name,compact(
            '_templates',
            '_components',
            '_personnels',
            '_main_structures',
            '_structures',
            '_positions',
            '_ranks',
            '_personnel_list_by_name',
            '_statuses'));
    }
}
