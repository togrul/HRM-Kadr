<?php

namespace App\Livewire\StaffSchedule;

use App\Livewire\Traits\StaffCrud;
use App\Models\StaffSchedule;
use Livewire\Component;

class EditStaff extends Component
{
    use StaffCrud;

    public $model;

    public function mount()
    {
        $this->title = __('Edit staff');
        $this->model = StaffSchedule::with(['structure','position'])->findOrFail($this->staffModel);
        $this->staff = [
            'structure_id' => $this->model->structure_id,
            'position_id' => $this->model->position_id,
            'total' => $this->model->total,
            'filled' => $this->model->filled,
            'vacant' => $this->model->vacant,
        ];

        $this->structureId = $this->model->structure_id;
        $this->structureName = $this->model->structure->name;
        $this->positionId = $this->model->position_id;
        $this->positionName = $this->model->position->name;
    }

    public function store()
    {
        $this->validate();

        $this->model->update($this->staff);

        $this->dispatch('staffAdded',__('Staff was updated successfully!'));
    }
}
