<?php

namespace App\Livewire\Orders;

use App\Livewire\Traits\OrderCrud;
use App\Models\Order;
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

            // insert log components
            $this->attachComponents($order_log,$_component_ids,'create');

            // get attributes and insert to attributes table
            $this->saveAttribute($order_log,$_attributes,'create');


            //insert order log personnels eger candidate dirse.Service cagir
            $tabel_no_list = $this->order['order_id'] == Order::IG_EMR
                            ? resolve(ImportCandidateToPersonnel::class)->handle($this->components,$this->order['status_id'])
                            : $_personnel_ids;

            //insert
            $_order_personnels = $this->formatOrderPersonnels($tabel_no_list,$_component_ids);

            $order_log->personnels()->attach($_order_personnels);

            (new OrderConfirmedService($order_log))->handle($_personnel_ids);
        });

        $this->dispatch('orderAdded',__('Order was added successfully!'));
    }
}
