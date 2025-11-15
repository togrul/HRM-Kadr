<?php

namespace App\Livewire\Forms;

use App\Models\Rank;
use Illuminate\Support\Arr;
use Livewire\Attributes\Validate;
use Livewire\Form;

class RankForm extends Form
{
    public ?Rank $rank;

    #[Validate('required|integer|min:1')]
    public $id;

    public $rank_category_id;

    #[Validate('required|min:2')]
    public $name_az;

    public $name_en;

    public $name_ru;

    public $duration;

    public $is_active = true;

    protected function validationAttributes()
    {
        return [
            'name_az' => __('Name'),
        ];
    }

    public function setPost(Rank $rank)
    {
        $this->rank = $rank;

        $this->id = $rank->id;
        $this->rank_category_id = $rank->rank_category_id;
        $this->name_az = $rank->name_az;
        $this->name_en = $rank->name_en;
        $this->name_ru = $rank->name_ru;
        $this->duration = $rank->duration;
        $this->is_active = (bool) $rank->is_active;
    }

    public function create()
    {
        $this->validate();

        $updateData = Arr::except($this->all(),'rank');

        Rank::create($updateData);
    }

    public function update()
    {
        $this->validate();
        $updateData = Arr::except($this->all(),'rank');
        $this->rank->update(
            $updateData
        );
    }
}
