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

        if (!$this->orderModelData) {
            abort(403);
            return;
        }

        $this->order = [
            'order_id' => $this->orderModelData->order_id,
            'order_no' => $this->orderModelData->order_no,
            'given_date' => Carbon::parse($this->orderModelData->given_date)->format('d.m.Y'),
            'given_by' => $this->orderModelData->given_by,
            'given_by_rank' => $this->orderModelData->given_by_rank,
            'status_id' => $this->orderModelData->status_id,
            'order_type_id' => [
                'id' => $this->orderModelData->order_type_id,
                'name' => $this->orderModelData->orderType->name,
            ],
        ];

        $this->showComponent = $this->orderModelData->order_type_id > 0;
        $this->selectedTemplate = $this->orderModelData->order_type_id;
        $this->componentRows = $this->orderModelData->components->count();
        $this->selectedBlade = $this->orderModelData->order->blade;

        $componentsList = $this->orderModelData->components;

        $orderModelAttributes = $this->orderModelData->attributes;

        foreach ($orderModelAttributes as $key => $attributes)
        {
            $attr_list = $attributes->attributes;

            $this->selectedComponents[$key] = array_keys($attr_list);

            $this->components[$attributes->row_number]['component_id'] = [
                'id' => $componentsList[$attributes->row_number]->id,
                'name' => $componentsList[$attributes->row_number]->name,
            ];


            foreach ($attr_list as $ka => $attr)
            {
                $_replacedKey = str_replace( '$', '', $ka);

                if($this->selectedBlade == 'default')
                {
                    $_replacedKey = $_replacedKey == 'fullname' ? 'personnel' : $_replacedKey;
                }

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

                if($this->selectedBlade == 'default')
                {
                    $this->components[$attributes->row_number][$_colName] = $_colValue;
                }
                else
                {
                    if(in_array($_colName,['start_date', 'end_date','days']))
                    {
                        $this->components[$attributes->row_number][$_colName] = $_colValue;
                    }
                }
            }

            if($this->selectedBlade == 'vacation')
            {
                $this->selected_personnel_list = $orderModelAttributes
                    ->groupBy('row_number')
                    ->map(function ($items,$rowIndex) use($attr_list) {
                        return $items->pluck('attributes')->map(function ($att) use($attr_list,$rowIndex) {

                            $transformed = collect($att)
                                ->except(['$start_date', '$end_date','$days'])
                                ->mapWithKeys(function ($value, $key) {
                                    return [str_replace( '$', '', $key) => $value['value']];
                                })->toArray();

                            $transformed['row'] = $rowIndex;
                            $transformed['key'] = Personnel::whereRaw("CONCAT(surname,' ',name,' ',patronymic) = ?",[$transformed['fullname']])->value('tabel_no');

                            return $transformed;
                        });
                    })
                    ->toArray();
            }
        }

        if($this->selectedBlade == 'vacation')
        {
            $this->selected_personnel_list['personnels'] = $this->orderModelData->personnels->pluck('tabel_no')->all();
        }
        $this->originalComponents = match($this->selectedBlade){
            'default' => $this->components,
            'vacation' => $this->selected_personnel_list,
        };
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

            $this->attachComponents($this->orderModelData,$_component_ids,'update');

            //get attributes and insert to attributes table
            $this->saveAttribute($this->orderModelData,$_attributes,'update');

            //insert order log personnels eger candidate-dirse. Service cagir

            if($this->selectedBlade == 'default')
            {
                if(!empty($_new_component_list))
                {
                    $tabel_no_list = $this->order['order_id'] == 1010
                        ? resolve(ImportCandidateToPersonnel::class)->handle($_new_component_list,$this->order['status_id'])
                        : Personnel::find(collect($_new_component_list)->pluck('personnel_id.id'))->pluck('tabel_no')->toArray();
                    $component_ids = collect($_new_component_list)->pluck('component_id.id')->toArray();

                    $_order_personnels = $this->formatOrderPersonnels($tabel_no_list,$component_ids);
                    // insert
                    $this->orderModelData->personnels()->attach($_order_personnels);
                }
            }
            else
            {
                $component_ids = collect($this->fillVacationPersonnelsToComponents())
                    ->values()
                    ->pluck('component_id.id')
                    ->all();

                $_order_personnels = $this->formatOrderPersonnels($this->selected_personnel_list['personnels'], $component_ids);

                $this->orderModelData->personnels()->sync($_order_personnels);
            }

            (new OrderConfirmedService($this->orderModelData))->handle($_personnel_ids,'update');
        });

        $this->dispatch('orderAdded',__('Order was updated successfully!'));
    }
}
