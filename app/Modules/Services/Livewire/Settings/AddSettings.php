<?php

namespace App\Modules\Services\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class AddSettings extends Component
{
    public $settings = [];

    protected function rules()
    {
        return [
            'settings.name' => 'required|string',
            'settings.value' => 'required',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'settings.name' => __('services::common.labels.name'),
            'settings.value' => __('services::common.labels.value'),
        ];
    }

    public function store()
    {
        $this->validate();

        Setting::create($this->settings);

        $this->dispatch('settingsUpdated', __('services::settings.messages.saved'));

        $this->settings = [];
    }

    public function render()
    {
        return view('services::livewire.services.settings.add-settings');
    }
}
