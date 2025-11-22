<?php

namespace App\Modules\Orders\Livewire;

use App\Livewire\Traits\OrderCrud;
use App\Models\Order;
use App\Models\OrderLog;
use App\Services\ImportCandidateToPersonnel;
use App\Services\OrderConfirmedService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AddOrder extends Component
{
    use AuthorizesRequests;
    use OrderCrud;

    public function store()
    {
        $data = $this->fillCrudData();
        if (! is_array($data)) {
            return $data;
        }
        [$_attributes,$_personnel_ids,$_component_ids] = [$data['attributes'], $data['personnel_ids'], $data['component_ids']];
        DB::transaction(function () use ($_attributes, $_personnel_ids, $_component_ids, $data) {
            $payload = $this->orderForm->payload();

            $created = [
                'order_type_id' => $payload['order_type_id'],
                'order_id' => $payload['order_id'],
                'order_no' => $payload['order_no'],
                'given_date' => Carbon::parse($payload['given_date'])->format('Y-m-d'),
                'given_by' => $payload['given_by'],
                'given_by_rank' => $payload['given_by_rank'],
                'status_id' => $payload['status_id'],
            ];

            if ($this->selectedBlade == Order::BLADE_BUSINESS_TRIP) {
                $created['description'] = $payload['description'];
            }
            //create order logs
            $order_log = OrderLog::create($created);

            $this->componentPersister->sync($order_log, $_component_ids);

            // get attributes and insert to attributes table
            $this->saveAttribute($order_log, $_attributes, 'create');

            //insert order log personnels eger candidate dirse.Service cagir
            $tabel_no_list = $this->isCandidateOrder()
                            ? (new ImportCandidateToPersonnel)->handle($this->componentForms, $payload['status_id'])
                            : $_personnel_ids;

            //insert
            $this->personnelPersister->attachAssignments($order_log, $tabel_no_list, $_component_ids);
            // vacation days leri bura gondermek ucun usul. ancaq vacation table i olanda.
            // shert qoymaq ayrica array gondermek.
            $extraData = match ($this->selectedBlade) {
                Order::BLADE_VACATION => collect($data['vacancy_list'])->except('personnels')->toArray(),
                default => []
            };
            (new OrderConfirmedService($order_log, $extraData))->handle($_personnel_ids);
        });

        $this->dispatch('orderAdded', __('Order was added successfully!'));

        return null;
    }
}
