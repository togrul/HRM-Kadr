<?php

namespace App\Livewire\Traits;

use App\Enums\StructureEnum;
use App\Helpers\UsefulHelpers;
use App\Models\Candidate;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Rank;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Services\CheckVacancyService;
use App\Services\WordSuffixService;
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
                'components.*.rank_id.id' => 'nullable|int|exists:ranks,id',
                'components.*.personnel_id.id' => 'required|int',
                'components.*.day' => 'required',
                'components.*.month' => 'required|string',
                'components.*.year' => 'required',
                'components.*.structure_main_id.id' => 'required|int|exists:structures,id',
                'components.*.structure_id.id' => 'required|int|exists:structures,id',
                'components.*.position_id.id' => 'required|int|exists:positions,id',
                'components.*.name' => 'required|string',
                'components.*.surname' => 'required|string'
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
            'components.*.surname' => __('Surname')
        ];
    }

    public function setStructure($id,$list,$field,$key,$isCoded)
    {
        $model = Structure::find($id);

        $models = Structure::find($model->getAllParentIds());
        $value = "";

        $suffixService = new WordSuffixService();

        foreach ($models as $parent)
        {
            $level_name = __(strtolower((collect(StructureEnum::cases())->pluck('name','value')[$parent->level])));
            $level_with_suffix = $parent->level > 1
                                ? $suffixService->getMultiSuffix($level_name)
                                : $suffixService->getStructureSuffix($level_name);
            $data = $isCoded
                    ? $parent->code. $suffixService->getNumberSuffix($parent->code) . " " . $level_with_suffix . " "
                    : $suffixService->getStructureSuffix($parent->name) . " ";
            $value .= $data;
        }
        $this->{$list}[$key][$field] = [
                'id' => $id,
                'name' => $value
        ];
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
        $this->fillEmptyComponent();
        if(empty($this->selectedOrder))
        {
            $this->order['order_id'] = OrderType::where('id',$value)->value('order_id');
        }
    }

    #[On('dynamicSelectChanged')]
    public function dynamicSelectChanged($value,$field,$rowKey = null)
    {
        if($field == 'personnel_id')
        {
            $personnelModel = $this->order['order_id'] == 1010
                            ? Candidate::find($value)
                            : Personnel::find($value);

            $this->components[$rowKey]['name'] = $personnelModel->name;
            $this->components[$rowKey]['surname'] = $personnelModel->surname;
        }

        $this->coded_list[$rowKey] = $field == 'structure_main_id' && $value == 1;
    }

    protected function resetFields()
    {
        $this->components = [];
        $this->componentRows = 1;
    }

    protected function fillEmptyComponent()
    {
        $this->components[] = [
            'rank_id' => [
                'id' => null,
                'name' => '---'
            ],
            'component_id' => [
                'id' => null,
                'name' => '---'
            ],
            'personnel_id' => [
                'id' => null,
                'name' => '---'
            ],
            'structure_main_id' => [
                'id' => null,
                'name' => '---'
            ],
            'structure_id' => [
                'id' => null,
                'name' => '---'
            ],
            'position_id' => [
                'id' => null,
                'name' => '---'
            ]
        ];

        $this->coded_list[] = false;
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
                    'component_id' => $keyComponent,
                    default => "$".$keyComponent
                };

                $_modified_component[$key][$_edit_key] = $keyComponent == 'component_id'
                    ? $valueComponent['id']
                    : $valueComponent;
            }
        }

        return $_modified_component;
    }

    public function updateVacancy()
    {
        $staff_schedule = StaffSchedule::where('structure_id', $this->vacancy_list['structure_id'])
                                    ->where('position_id' , $this->vacancy_list['position_id'])
                                    ->first();

        if(!$staff_schedule)
        {
           StaffSchedule::create([
               'structure_id' => $this->vacancy_list['structure_id'],
               'position_id' => $this->vacancy_list['position_id'],
               'total' => $this->vacancy_list['count'],
               'filled' => 0,
               'vacant' => $this->vacancy_list['count']
           ]);
        }
        else
        {
            $staff_schedule->update([
                'total' => $staff_schedule->total + $this->vacancy_list['count'],
                'vacant' => $staff_schedule->vacant + $this->vacancy_list['count']
            ]);
        }

        $this->dispatch('vacancyUpdated',__('Vacancy was added successfully!'));
    }

    private function modifyCodedList()
    {
        $this->coded_list = array_map(function ($value){
            return $value === 1 ? true : false;
        },collect($this->components)->pluck('structure_main_id.id')->toArray());
    }

    private function prepareToCrud() : array
    {
        $_attributes = $this->modifyComponentList($this->components);
        $_personnel_ids = collect($this->components)->pluck('personnel_id.id')->toArray();
        $_component_ids = collect($this->components)->pluck('component_id.id')->toArray();

        $this->validate($this->validationRules()['main']);
        $this->validate($this->validationRules()['dynamic']);

        /** secilen vezifelerin bos olub olmadigin yoxlayir yoxdursa vakansiya yaratmaga imkan verir **/
        $list_for_vacancy = !empty($this->originalComponents)
                            ? UsefulHelpers::compareMultidimensionalArrays($this->components,$this->originalComponents)
                            : $this->components;

        $this->vacancy_list = resolve(CheckVacancyService::class)->handle($list_for_vacancy);

        return [
            'attributes' => $_attributes,
            'personnel_ids' => $_personnel_ids,
            'component_ids' => $_component_ids,
            'vacancy_list' => $list_for_vacancy,
            'message' => !empty($this->vacancy_list) ? $this->vacancy_list['message'] : ""
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

        if(array_key_exists('order_id',$this->order) && $this->order['order_id'] == 1010)
        {
            //yalniz emre hazir statusunda olan namizedlerin siyahisi cixsin
            $_personnels = Candidate::when(!empty($this->searchPersonnel),function ($q){
                $q->where(function ($query){
                    $query->where('name','LIKE',"%{$this->searchPersonnel}%")
                        ->orWhere('surname','LIKE',"%{$this->searchPersonnel}%");
                });
            })
                ->whereNotIn('id',$_personnel_id_list)
                ->where('status_id',30)
                ->get();
        }
        else
        {
            $_personnels = Personnel::when(!empty($this->searchPersonnel),function ($q){
                $q->where(function ($query){
                    $query->where('name','LIKE',"%{$this->searchPersonnel}%")
                        ->orWhere('surname','LIKE',"%{$this->searchPersonnel}%");
                });
            })
                ->whereNotIn('id',$_personnel_id_list)
                ->whereNull('leave_work_date')
                ->orderBy('position_id')
                ->orderBy('structure_id')
                ->get();
        }

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

        $view_name = !empty($this->orderModel)
                    ? 'livewire.orders.edit-order'
                    : 'livewire.orders.add-order';


        return view($view_name,compact('_templates','_components','_personnels','_main_structures','_structures','_positions','_ranks','_statuses'));
    }
}
