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

class AddOrder extends Component
{
    use OrderCrud;
    public function store()
    {
        $data = $this->fillCrudData();
        if(!is_array($data))
        {
           return $data;
        }
        [$_attributes,$_personnel_ids,$_component_ids] = [$data['attributes'],$data['personnel_ids'],$data['component_ids']];

        DB::transaction(function () use($_attributes,$_personnel_ids,$_component_ids){
            //create order logs
            $order_log = OrderLog::create([
                'order_type_id' => $this->order['order_type_id']['id'],
                'order_id' => $this->order['order_id'],
                'order_no' => $this->order['order_no'],
                'given_date' => Carbon::parse($this->order['given_date'])->format('Y-m-d'),
                'given_by' => $this->order['given_by'],
                'given_by_rank' => $this->order['given_by_rank'],
                'status_id' => $this->order['status_id'],
            ]);

            //insert log components
            foreach($_component_ids as $key => $_component)
            {
                $order_log->components()->attach([
                    $_component => ['row_number' => $key]
                ]);
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

                $order_log->attributes()->create([
                    'component_id' => $component_id,
                    'attributes' => $attr_data,
                    'row_number' => $k
                ]);
            }

            //insert order log personnels eger candidate dirse.Service cagir
            $tabel_no_list = $this->order['order_id'] == 1010
                            ? resolve(ImportCandidateToPersonnel::class)->handle($this->components,$this->order['status_id'])
                            : Personnel::find($_personnel_ids)->pluck('tabel_no')->toArray();

            //insert
            $_order_personnels = array_map(function($value){
                                    return ['component_id' => $value];
                                },array_combine($tabel_no_list,$_component_ids));

            $order_log->personnels()->attach($_order_personnels);

            resolve(OrderConfirmedService::class)->handle($order_log,$_personnel_ids);
        });

        $this->dispatch('orderAdded',__('Order was added successfully!'));
    }
}
