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
        $_attributes = $this->modifyComponentList($this->components);
        $_personnel_ids = collect($this->components)->pluck('personnel_id.id')->toArray();
        $_component_ids = collect($this->components)->pluck('component_id.id')->toArray();
        $_converted_component_ids = [];
        foreach($_component_ids as $key => $component_id)
        {
            $_converted_component_ids[$component_id] = ['row_number' => $key];
        }

        $this->validate($this->validationRules()['main']);
        $this->validate($this->validationRules()['dynamic']);

        DB::transaction(function () use($_converted_component_ids,$_attributes,$_personnel_ids,$_component_ids){

            //create order logs
            $order_log = OrderLog::create([
                'order_type_id' => $this->order['order_type_id']['id'],
                'order_id' => $this->order['order_id'],
                'order_no' => $this->order['order_no'],
                'given_date' => Carbon::parse($this->order['given_date'])->format('Y-m-d'),
                'given_by' => $this->order['given_by'],
                'given_by_rank' => $this->order['given_by_rank'],
                'is_coded' => $this->order['is_coded'],
                'status_id' => $this->order['status_id'],
            ]);

            //insert log components
            $order_log->components()->attach($_converted_component_ids);

            // get attributes and insert to attributes table
            foreach ($_attributes as $_attr)
            {
                $component_id = $_attr['component_id'];
                unset($_attr['component_id']);

                foreach ($_attr as $key => $value) {
                    $order_log->attributes()->create([
                        'component_id' => $component_id,
                        'attribute_key' => $key,
                        'attribute_value' => $value
                    ]);
                }
            }

            //insert order log personnels eger candidate dirse.Service cagir
            $tabel_no_list = $this->order['order_id'] == 1010
                            ? resolve(ImportCandidateToPersonnel::class)->handle($_personnel_ids,$this->order['status_id'])
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
