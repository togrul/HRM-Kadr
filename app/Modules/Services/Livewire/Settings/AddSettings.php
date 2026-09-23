<?php

namespace App\Modules\Services\Livewire\Settings;

use App\Models\Setting;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddSettings extends Component
{
    use AuthorizesSettingsAccess;

    public $settings = [];

    protected function rules(): array
    {
        return [
            'settings.name' => 'required|string',
            'settings.value' => 'required',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'settings.name' => __('services::common.labels.name'),
            'settings.value' => __('services::common.labels.value'),
        ];
    }

    public function store(): void
    {
        $this->validate();

        Setting::create($this->settings);

        $this->dispatch('settingsUpdated', __('services::settings.messages.saved'));

        $this->settings = [];
    }

    public function render(): View
    {
        return view('services::livewire.services.settings.add-settings');
    }
}
