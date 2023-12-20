<?php

namespace App\Livewire\Services\Settings;

use App\Models\Setting;
use Livewire\Component;

class AddSettings extends Component
{
    public $settings = [];

    protected function rules()
    {
        return [
            'settings.name' => 'required|string',
            'settings.value' => 'required'
        ];
    }

    protected function validationAttributes()
    {
        return [
            'settings.name' => __('Name'),
            'settings.value' => __('Value'),
        ];
    }

    public function store()
    {
        $this->validate();

        Setting::create($this->settings);

        $this->dispatch('settingsUpdated',__('Setting was added successfully!'));

        $this->settings = [];
    }

    public function render()
    {
        return view('livewire.services.settings.add-settings');
    }
}
