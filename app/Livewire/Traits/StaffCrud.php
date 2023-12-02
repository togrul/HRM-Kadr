<?php

namespace App\Livewire\Traits;

use App\Models\Position;
use App\Models\Personnel;
use App\Models\Structure;
use Illuminate\Support\Arr;

trait StaffCrud
{
    public $title;
    public $structureId,$structureName,$searchStructure;

    public $positionId,$positionName,$searchPosition;

    public $staff = [];

    public $staffModel;

    public function rules() 
    {
        return [
            'staff.structure_id' => 'required|int|exists:structures,id',
            'staff.position_id' => 'required|int|exists:positions,id',
            'staff.total' => 'required',
            'staff.filled' => 'required',
            'staff.vacant' => 'required',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'staff.structure_id'=> __('Structure'),
            'staff.position_id'=> __('Position'),
            'staff.total'=> __('Total'),
            'staff.filled'=> __('Filled'),
            'staff.vacant'=> __('Vacant'),
        ];
    }

    public function setData($model,$key,$content,$name,$id)
    {
        $this->{$content.'Id'} = $id;
        $this->{$content.'Name'} = $name;
        if(!empty($id))
        {
            $this->{$model}[$key] = $id; 
        }
        else
        {
            unset($this->{$model}[$key]);
        }

        if(Arr::has($this->staff, ['structure_id', 'position_id']))
        {
            if($this->staff['structure_id'] > 0 && $this->staff['position_id'] > 0)
            {
                $this->staff['filled'] = Personnel::whereNull('leave_work_date')
                    ->where('structure_id',$this->staff['structure_id'])
                    ->where('position_id',$this->staff['position_id'])
                    ->get()
                    ->count();
            }
        }
    }

    public function render()
    {
        $structures = Structure::when(!empty($this->searchStructure),function($q){
            $q->where('name','LIKE',"%{$this->searchStructure}%");
        })
                ->get();

        $positions = Position::when(!empty($this->searchPosition),function($q){
                    $q->where('name','LIKE',"%{$this->searchPosition}%");
                })
                ->get();

        $view_name = !empty($this->staffModel)  ? 'livewire.staff-schedule.edit-staff' : 'livewire.staff-schedule.add-staff';

        return view($view_name,compact('structures','positions'));
    }
}