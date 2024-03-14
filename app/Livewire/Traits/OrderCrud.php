<?php

namespace App\Livewire\Traits;

use App\Models\Candidate;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Structure;
use Livewire\Attributes\On;

trait OrderCrud
{
    use SelectListTrait;

    //esas cedvelin listi
    public $order = [];
    public $searchTemplate,$searchPersonnel,$searchStructure,$searchPosition;
    public $title;

    //edited data model
    public $orderModel;

    //selected order category - from all order list
    public ?int $selectedOrder;

    //template secilende asagida komponentlerin gorunusu
    public $showComponent = false;

    //dinamik componentlerin secildiyi list
    public $components = [];

    //add row hissede dinamik listin generasiya olunmasi
    public $componentRows;

    //secilen sablon modelin ID si
    public $selectedTemplate;

    //secilen component - dinamik fieldleri generasiya elemek ucun
    public $selectedComponents = [];

    public function validationRules()
    {
        return [
            'main' => [
                'order.order_type_id.id' => 'required|int|exists:order_types,id',
                'order.order_no' => 'required|min:3|unique:order_logs,order_no'. (!empty($this->orderModel) ? ','.$this->orderModel : ''),
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
            $this->order['order_id'] = OrderType::where('id',$value)->value('order_id');
    }

    #[On('dynamicSelectChanged')]
    public function dynamicSelectChanged($value,$field,$rowKey = null)
    {
        if($field == 'personnel_id')
        {
            $personnelModel = null;
            if($this->order['order_id'] == 1010)
            {
                $personnelModel = Candidate::find($value);
            }
            else
            {
                $personnelModel = Personnel::find($value);
            }

            $this->components[$rowKey]['name'] = $personnelModel->name;
            $this->components[$rowKey]['surname'] = $personnelModel->surname;
        }
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
//        data_forget($components,'*.component_id');
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

                $_modified_component[$key][$_edit_key] = is_array($valueComponent)
                    ? $keyComponent == 'component_id' ? $valueComponent['id'] : $valueComponent['name']
                    : $valueComponent;
            }
        }

        return $_modified_component;
    }

    public function mount()
    {
        if(!empty($this->orderModel))
        {
//            $this->fillOrder();
            $this->title = __('Edit order');
        }
        else
        {
            $this->title = __('Add order');
            $this->order['given_by'] = cache('settings')['Chief'];
            $this->order['given_by_rank'] = cache('settings')['Chief rank'];
            $this->order['order_id'] = $this->selectedOrder;
            $this->order['is_coded'] = false;
            $this->componentRows = 1;
        }
    }

    public function render()
    {
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

        if(array_key_exists('order_id',$this->order) && $this->order['order_id'] == 1010)
        {
            //yalniz emre hazir statusunda olan namizedlerin siyahisi cixsin
            $_personnels = Candidate::when(!empty($this->searchPersonnel),function ($q){
                $q->where(function ($query){
                    $query->where('name','LIKE',"%{$this->searchPersonnel}%")
                        ->orWhere('surname','LIKE',"%{$this->searchPersonnel}%");
                });
            })
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
                ->whereNull('leave_work_date')
                ->orderBy('position_id')
                ->orderBy('structure_id')
                ->get();
        }

        $_ranks = Rank::where('is_active',true)->get();

        $_main_structures = Structure::where('code',0)->orderBy('id')->get();
        $_structures = Structure::when(!empty($this->searchStructure),function ($q){
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
