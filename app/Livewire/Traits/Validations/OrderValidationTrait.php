<?php

namespace App\Livewire\Traits\Validations;

use App\Models\Order;

trait OrderValidationTrait
{
    public function validationRules()
    {
        return [
            'main' => [
                'order.order_type_id.id' => 'required|int|exists:order_types,id',
                'order.order_no' => 'required|min:3|unique:order_logs,order_no'.(! empty($this->orderModel) ? ','.$this->orderModelData->id : ''),
                'order.given_date' => 'required',
                'order.status_id' => 'required|exists:order_statuses,id',
                'order.description.description' => $this->isForeignBusinessTrip() ? 'required|min:2' : '',
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
                'components.*.name' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|string' : '',
                'components.*.surname' => $this->selectedBlade == Order::BLADE_DEFAULT ? 'required|string' : '',
                'components.*.start_date' => in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP]) ? 'required|date' : '',
                'components.*.end_date' => in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP]) ? 'required|date|after:start_date' : '',
                'components.*.days' => $this->selectedBlade == Order::BLADE_VACATION ? 'required|int|min:0' : '',
                'components.*.location' => $this->selectedBlade == Order::BLADE_BUSINESS_TRIP ? 'required|string|min:2' : '',
                'components.*.meeting_hour' => $this->isInternalBusinessTrip() ? 'required|string' : '',
                'components.*.return_month' => $this->isInternalBusinessTrip() ? 'required|string' : '',
                'components.*.return_day' =>  $this->isInternalBusinessTrip() ? 'required|int|min:1|max:31' : '',
            ],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'order.order_type_id.id' => __('Template'),
            'order.order_no' => __('Order number'),
            'order.given_date' => __('Given date'),
            'order.status_id' => __('Status'),
            'order.description.description' => __('Description'),
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
            'components.*.location' => __('Location'),
            'components.*.meeting_hour' => __('Meeting hour'),
            'components.*.return_month' => __('Return month'),
            'components.*.return_day' => __('Return day'),
        ];
    }
}
