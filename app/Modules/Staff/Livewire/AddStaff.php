<?php

namespace App\Modules\Staff\Livewire;

use App\Livewire\Traits\StaffCrud;
use App\Models\StaffSchedule;
use Livewire\Component;

class AddStaff extends Component
{
    use StaffCrud;

    protected function checkStructure()
    {
        return StaffSchedule::where('structure_id', $this->structureId)->first();
    }

    public function store()
    {
        if (empty($this->staff)) {
            return;
        }

        if (! empty($this->checkStructure())) {
            $this->dispatch('staffScheduleError', __('This structure has already been added!'));

            return;
        }

        $this->validate(array_merge(
            $this->rules(),
            ['structureId' => 'required|int|exists:structures,id']
        ));

        foreach ($this->staff as $sta) {
            $data = $sta;
            unset($data['position']);
            StaffSchedule::create($data);
        }

        $this->dispatch('staffAdded', __('Staff was added successfully!'));
    }

    public function mount()
    {
        $this->authorize('add-staff');
        $this->title = __('New staff');
        $this->structureId = null;
    }
}
