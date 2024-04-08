<?php

namespace App\Livewire\Orders\Templates;

use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
use App\Models\OrderType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class SetType extends Component
{
    use SideModalAction,AuthorizesRequests;

    public $types = [];
    public $title;
    public $templateModel;

    public $selectedType;
    public $selectedModel;

    public function rules()
    {
        return [
            'types.name' => 'required|string|min:2|unique:order_types,name',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'types.name' => __('Name'),
        ];
    }

    public function addType()
    {
        $this->validate();

        $this->templateModel->types()->create($this->types);

        $this->clearField();

        $this->dispatch('typesUpdated',__('Type was added successfully!'));
    }

    public function removeType($_typeId)
    {
        OrderType::find($_typeId)->delete();
        $this->dispatch('typesUpdated',__('Type was updated successfully!'));
    }

    public function editType($_typeId)
    {
        $this->selectedType = $_typeId;
        $this->selectedModel = OrderType::find($_typeId);
        $this->types['name'] =  $this->selectedModel->name;
    }

    public function updateModel()
    {
        $this->selectedModel->update($this->types);
        $this->clearField();
        $this->dispatch('typesUpdated',__('Type was added successfully!'));
    }

    public function cancelUpdate()
    {
        $this->clearField();
    }

    protected function clearField()
    {
        $this->types = [];
        $this->selectedType = null;
        $this->resetValidation();
    }

    public function mount()
    {
        $this->title = __('Set Type');
        $this->templateModel = Order::findOrFail($this->templateModel);

    }

    public function render()
    {
        $_order_types = $this->templateModel->types;
        return view('livewire.orders.templates.set-type',compact('_order_types'));
    }
}
