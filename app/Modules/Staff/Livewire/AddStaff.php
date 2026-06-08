<?php

namespace App\Modules\Staff\Livewire;

use App\Modules\Staff\Support\Traits\StaffCrud;
use App\Models\StaffSchedule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
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
            $this->dispatch('staffScheduleError', __('staff::common.messages.structure_exists'));

            return;
        }

        $this->syncComputedStaffRows();

        $this->validate(array_merge(
            $this->rules(),
            ['structureId' => ['required', 'integer', Rule::in($this->allowedStructureIds())]]
        ));

        foreach ($this->staff as $sta) {
            $data = $sta;
            unset($data['position']);
            unset($data['hide_position']);
            StaffSchedule::create($data);
        }

        $this->dispatch('staffAdded', __('staff::common.messages.staff_added'));
    }

    public function mount(?int $selectedStructureId = null)
    {
        $this->authorize('create', StaffSchedule::class);
        $this->title = __('staff::common.titles.new_staff');
        $this->structureId = $selectedStructureId && in_array($selectedStructureId, $this->allowedStructureIds(), true)
            ? $selectedStructureId
            : null;
    }
}
