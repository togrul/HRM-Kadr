<?php

namespace App\Livewire\Orders;

use App\Livewire\Traits\OrderCrud;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Services\ImportCandidateToPersonnel;
use App\Services\OrderConfirmedService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditOrder extends Component
{
    use OrderCrud;

    public $orderModelData;

    protected function fillOrder()
    {
        $this->orderModelData = OrderLog::with(['order','components','personnels','status','attributes'])
                                ->where('order_no',$this->orderModel)
                                ->first();

        $this->order = [
            'order_id' => $this->orderModelData->order_id,
            'order_no' => $this->orderModelData->order_no,
            'given_date' => Carbon::parse($this->orderModelData->given_date)->format('d.m.Y'),
            'given_by' => $this->orderModelData->given_by,
            'given_by_rank' => $this->orderModelData->given_by_rank,
            'status_id' => $this->orderModelData->status_id,
        ];

        $this->order['order_type_id'] = [
            'id' => $this->orderModelData->order_type_id,
            'name' => $this->orderModelData->orderType->name,
        ];

        $this->showComponent = $this->orderModelData->order_type_id > 0;
        $this->selectedTemplate = $this->orderModelData->order_type_id;
        $this->componentRows = $this->orderModelData->components->count();

        $componentsList = $this->orderModelData->components;

        foreach ($this->orderModelData->attributes as $key => $attributes)
        {
            $attr_list = $attributes->attributes;

            $this->selectedComponents[$key] = array_keys($attr_list);

            $this->components[$key]['component_id'] = [
                'id' => $componentsList[$key]->id,
                'name' => $componentsList[$key]->name,
            ];
            foreach ($attr_list as $ka => $attr)
            {
                $_replacedKey = str_replace( '$', '', $ka);
                $_replacedKey = $_replacedKey == 'fullname' ? 'personnel' : $_replacedKey;

                if(!empty($attr['id']) || $attr['value'] == '---')
                {
                    $_colName = "{$_replacedKey}_id";
                    $_colValue = [
                        'id' => $attr['id'],
                        'name' => $attr['value'],
                    ];
                }
                else
                {
                    $_colName = $_replacedKey;
                    $_colValue = $attr['value'];
                }

                $this->components[$key][$_colName] = $_colValue;
            }
        }

        $this->originalComponents = $this->components;
    }

    public function store()
    {
        $data = $this->fillCrudData();
        if(!is_array($data))
        {
            return $data;
        }

        [$_attributes,$_personnel_ids,$_component_ids, $_new_component_list] = [$data['attributes'],$data['personnel_ids'],$data['component_ids'],$data['vacancy_list']];

        DB::transaction(function () use($_attributes,$_personnel_ids,$_component_ids,$_new_component_list){
            $this->orderModelData->update([
                'order_type_id' => $this->order['order_type_id']['id'],
                'order_id' => $this->order['order_id'],
                'order_no' => $this->order['order_no'],
                'given_date' => Carbon::parse($this->order['given_date'])->format('Y-m-d'),
                'given_by' => $this->order['given_by'],
                'given_by_rank' => $this->order['given_by_rank'],
                'status_id' => $this->order['status_id'],
            ]);

            foreach($_component_ids as $key => $_component) {
                    $component_exists = $this->orderModelData->components()
                                    ->where('row_number' , $key)
                                    ->where('component_id', $_component)
                                    ->first();

                    if(!$component_exists) {
                        $this->orderModelData->components()->attach([
                            $_component => ['row_number' => $key]
                        ]);
                }
            }

            //get attributes and insert to attributes table
            foreach ($_attributes as $k => $_attr)
            {
                $component_id = $_attr['component_id'];
                unset($_attr['component_id']);

                foreach ($_attr as $key => $value) {
                    $attr_data[$key] = [
                        'id' =>  is_array($value) ? $value['id'] : null,
                        'value' => is_array($value) ? $value['name'] : $value
                    ];
                }

                $this->orderModelData->attributes()->updateOrCreate(
                    [
                        'component_id' => $component_id,
                        'order_no' => $this->orderModelData->order_no,
                        'row_number' => $k
                    ],
                    [
                        'attributes' => $attr_data
                    ]
                );
            }
            //insert order log personnels eger candidate dirse.Service cagir
            if(!empty($_new_component_list))
            {
                $tabel_no_list = $this->order['order_id'] == 1010
                    ? resolve(ImportCandidateToPersonnel::class)->handle($_new_component_list,$this->order['status_id'])
                    : Personnel::find($_new_component_list)->pluck('tabel_no')->toArray();
                //insert
                $_order_personnels = array_map(function($value){
                    return ['component_id' => $value];
                },array_combine($tabel_no_list,collect($_new_component_list)->pluck('component_id.id')->toArray()));

                $this->orderModelData->personnels()->attach($_order_personnels);
            }

            resolve(OrderConfirmedService::class)->handle($this->orderModelData,$_personnel_ids,'update');
        });

        $this->dispatch('orderAdded',__('Order was updated successfully!'));
    }
}
