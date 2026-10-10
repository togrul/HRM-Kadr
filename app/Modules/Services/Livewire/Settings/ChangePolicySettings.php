<?php

namespace App\Modules\Services\Livewire\Settings;

use App\Modules\Personnel\Contracts\ManagesPersonnelChangePolicy;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Admin → Tənzimləmələr → «Dəyişiklik siyasəti»: hər işçi sahə qrupunun rejimi (sərbəst /
 * jurnal / yalnız əmrlə). Siyasətin özü Personnel modulundadır; bu ekran yalnız onun
 * contract-ı ilə danışır. Hər sorğu həm `access-settings`, həm də `manage-change-policy`
 * icazəsini yoxlayır — hazırlanmış Livewire sorğusu da icazəsiz yaza bilmir.
 */
class ChangePolicySettings extends Component
{
    use AuthorizesSettingsAccess;

    /**
     * Seçilmiş rejimlər: qrup → rejim (dəyişən kimi yadda saxlanılır).
     *
     * @var array<string, string>
     */
    public array $modes = [];

    public function boot(): void
    {
        Gate::authorize('manage-change-policy');
    }

    public function mount(): void
    {
        $this->loadModes();
    }

    public function updatedModes(mixed $value, string $group): void
    {
        $policy = app(ManagesPersonnelChangePolicy::class);

        if (! $this->isKnownGroup($group) || ! is_string($value) || ! array_key_exists($value, $policy->modeOptions())) {
            $this->loadModes();
            $this->addError('modes.'.$group, __('services::settings.change_policy.messages.invalid'));

            return;
        }

        $policy->setMode($group, $value, auth()->id());
        $this->loadModes();

        $this->dispatch('settingsUpdated', __('services::settings.change_policy.messages.saved'));
    }

    public function resetGroup(string $group): void
    {
        if (! $this->isKnownGroup($group)) {
            $this->addError('modes.'.$group, __('services::settings.change_policy.messages.invalid'));

            return;
        }

        app(ManagesPersonnelChangePolicy::class)->resetToDefault($group, auth()->id());
        $this->loadModes();

        $this->dispatch('settingsUpdated', __('services::settings.change_policy.messages.reset'));
    }

    public function render(): View
    {
        $policy = app(ManagesPersonnelChangePolicy::class);

        return view('services::livewire.services.settings.change-policy', [
            'rows' => $policy->rows(),
            'modeOptions' => $policy->modeOptions(),
        ]);
    }

    private function loadModes(): void
    {
        $this->modes = collect(app(ManagesPersonnelChangePolicy::class)->rows())
            ->mapWithKeys(fn (array $row): array => [$row['group'] => $row['mode']])
            ->all();
    }

    private function isKnownGroup(string $group): bool
    {
        return in_array($group, array_column(app(ManagesPersonnelChangePolicy::class)->rows(), 'group'), true);
    }
}
