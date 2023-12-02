<?php

namespace App\Livewire\StaffSchedule;

use App\Livewire\Traits\StaffCrud;
use App\Models\Personnel;
use Livewire\Component;
use App\Models\StaffSchedule;

class AddStaff extends Component
{
    use StaffCrud;

    public function updatingStaff($value,$name)
    {
        if($name == 'total')
        {
            $this->staff['vacant'] = $value - $this->staff['filled'];
        }
    }
    
    public function store()
    {
        $this->validate();

        StaffSchedule::create($this->staff);

        $this->dispatch('staffAdded',__('Staff was added successfully!'));
    }

    public function mount()
    {
        $this->title = __('New staff');
        $this->structureName = $this->positionName = '---';
        $this->staff = [
            'total' => 0,
            'filled' => 0,
            'vacant' => 0,
        ];
    }
}
