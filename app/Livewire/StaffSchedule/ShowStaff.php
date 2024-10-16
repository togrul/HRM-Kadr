<?php

namespace App\Livewire\StaffSchedule;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use Livewire\Component;
use Livewire\WithPagination;

class ShowStaff extends Component
{
    use WithPagination;

    public $structureModel;

    public $positionModel;

    public $title;

    public function mount()
    {
        $structure = Structure::where('id', $this->structureModel)->value('name');
        $position = Position::where('id', $this->positionModel)->value('name');
        $this->title = "{$structure}($position)";
    }

    public function render()
    {
        $staffs = Personnel::with('structure')
            ->whereNull('leave_work_date')
            ->where('structure_id', $this->structureModel)
            ->where('position_id', $this->positionModel)
            ->paginate(20);

        return view('livewire.staff-schedule.show-staff', compact('staffs'));
    }
}
