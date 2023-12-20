<?php

namespace App\Livewire\Traits;

use App\Models\Position;
use App\Models\Personnel;
use App\Models\Structure;
use Illuminate\Support\Arr;

trait StaffCrud
{
    public $title;
    public $searchStructure;

    public $searchPosition;

    public $staff = [];

    public $staffModel;

    public $hidePosition = false;

    public function rules() 
    {
        return [
            'staff.*.structure_id' => 'required|int|exists:structures,id',
            'staff.*.position_id' => !$this->hidePosition ? 'required|int|exists:positions,id' : '',
            'staff.*.total' => 'required',
            'staff.*.filled' => 'required',
            'staff.*.vacant' => 'required',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'staff.*.structure_id' => __('Structure'),
            'staff.*.position_id' => __('Position'),
            'staff.*.total' => __('Total'),
            'staff.*.filled' => __('Filled'),
            'staff.*.vacant' => __('Vacant'),
        ];
    }

    public function updatedStaff($value,$name)
    {
        $value = empty($value) ? 0 : $value;
        $exploded = explode('.',$name);
        $_index = $exploded[0];
        $_name = $exploded[1];
        if($_name == 'total')
        {
            $this->staff[$_index]['vacant'] = $value - $this->staff[$_index]['filled'];
        }
    }

    public function addRow()
    {
        $this->staff[] = [
            'structure_id' => !empty($this->staffModel) ? $this->staffModel : $this->structureData[0]['structure_id'],
            'position_id' => null,
            'total' => 0,
            'filled' => 0,
            'vacant' => 0,
            'position' => [
                'id' => null,
                'name' => '---'
            ]
        ];

        $key = array_key_last($this->staff) > 0 ? array_key_last($this->staff)  : 0;
        $this->hidePositionAction($key);
    }

    public function deleteRow($row)
    {
        unset($this->staff[$row]);
    }

    public function setData($array_key,$model,$key,$content,$name,$id)
    {
        $this->searchPosition = null;
        if(!empty($id))
        {
            $this->{$model}[$array_key][$key] = $id; 
            $this->{$model}[$array_key][$content] = [
                'id' => $id,
                'name' => $name
            ];
        }
        else
        {
            $this->{$model}[$array_key][$key] = null;
            $this->{$model}[$array_key][$content] = [
                'id' => null,
                'name' => '---'
            ];
        }
       $this->fillAutoData($array_key,$model);       
    }

    protected function hidePositionAction($array_key)
    {
        $parent_id = Structure::where('id',$this->staff[$array_key]['structure_id'])->value('parent_id');
        if(empty($parent_id))
        {
            $this->hidePosition = true;
        }   
    }

    protected function fillAutoData($array_key,$model)
    {
        if(!empty($this->staff))
        {
            $this->hidePosition = false;
            if(empty($this->staffModel))
            {
                $this->staff[$array_key]['structure_id'] = $this->structureData[0]['structure_id'];
            }
         
            if(Arr::has($this->staff[$array_key], ['structure_id', 'position_id']))
            {
                $_structureId = $this->staff[$array_key]['structure_id'];
                $_positionId = $this->staff[$array_key]['position_id'];
                if($model == 'structureData')
                {
                    $this->hidePositionAction($array_key);
                    $this->staff[$array_key]['position_id'] = null;
                    if($_structureId > 0)
                    {
                        $_ids = Structure::with('subs')->find($_structureId)->getAllNestedIds();
                        $this->staff[$array_key]['filled'] = Personnel::whereNull('leave_work_date')
                            ->whereIn('structure_id',$_ids)
                            ->get()
                            ->count();
                    }
                }
                else
                {
                    if($_structureId > 0 && $_positionId > 0)
                    {
                        $this->staff[$array_key]['filled'] = Personnel::whereNull('leave_work_date')
                            ->where('structure_id',$_structureId)
                            ->where('position_id',$_positionId)
                            ->get()
                            ->count();
                    }
                }
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