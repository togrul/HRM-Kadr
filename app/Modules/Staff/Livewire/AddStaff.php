<?php

namespace App\Modules\Staff\Livewire;

use App\Modules\Staff\Support\Traits\StaffCrud;
use App\Models\StaffSchedule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AddStaff extends Component
{
    use AuthorizesRequests;
    use StaffCrud;

    protected function checkStructure()
    {
        return StaffSchedule::where('structure_id', $this->structureId)->first();
    }

    public function store()
    {
        $this->authorize('create', StaffSchedule::class);

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
        $this->authorize('create', StaffSchedule::class);
        $this->title = __('New staff');
        $this->structureId = null;
    }
}
