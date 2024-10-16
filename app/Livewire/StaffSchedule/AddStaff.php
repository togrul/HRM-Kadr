<?php

namespace App\Livewire\StaffSchedule;

use App\Livewire\Traits\StaffCrud;
use App\Models\StaffSchedule;
use Livewire\Component;

class AddStaff extends Component
{
    use StaffCrud;

    public $structureData;

    protected function checkStructure()
    {
        return StaffSchedule::where('structure_id', $this->structureData[0]['structure_id'])->first();
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

        $this->validate();

        foreach ($this->staff as $sta) {
            $data = $sta;
            unset($data['position']);
            StaffSchedule::create($data);
        }

        $this->dispatch('staffAdded', __('Staff was added successfully!'));
    }

    public function mount()
    {
        $this->title = __('New staff');
        $this->structureData = [
            [
                'structure_id' => -1,
                'structure' => [
                    'id' => -1,
                    'name' => '---',
                ],
            ],
        ];
    }
}
