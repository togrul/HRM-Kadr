<?php

namespace App\Modules\SidebarStructure\Livewire;

use App\Models\Menu;
use App\Models\Rank;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Services extends Component
{
    public ?string $selectedService = null;

    public function mount(): void
    {
        $this->selectedService = request()->get('selectedService');
    }

    public function selectService(string $service): void
    {
        $this->selectedService = $service;
        $this->dispatch('selectService', $service);
    }

    /**
     * Settings sections of the panel; a count is shown only where one row = one record.
     *
     * @return array<int,array{key:string,label:string,count:string|null}>
     */
    #[Computed]
    public function sections(): array
    {
        $sections = [
            ['key' => 'general', 'label' => __('services::common.labels.general'), 'count' => null],
            ['key' => 'candidate', 'label' => __('services::common.labels.candidate_preferences'), 'count' => null],
            ['key' => 'notifications-settings', 'label' => __('services::common.labels.notifications'), 'count' => null],
        ];

        // Dəyişiklik siyasəti öz icazəsi ilə (manage-change-policy) görünür.
        if (auth()->user()?->can('manage-change-policy') ?? false) {
            $sections[] = ['key' => 'change-policy', 'label' => __('services::common.labels.change_policy'), 'count' => null];
        }

        $sections[] = ['key' => 'menus', 'label' => __('services::common.labels.menus'), 'count' => (string) Menu::query()->count()];

        // Rollar və istifadəçilər ayrıca icazələrlə (manage-roles / manage-users) görünür.
        if (auth()->user()?->can('manage-roles') ?? false) {
            $sections[] = ['key' => 'roles', 'label' => __('services::common.navigation.roles_and_permissions'), 'count' => (string) Role::query()->count()];
        }

        if (auth()->user()?->can('manage-users') ?? false) {
            $sections[] = ['key' => 'users', 'label' => __('services::common.labels.users'), 'count' => (string) User::query()->count()];
        }

        $sections[] = ['key' => 'ranks', 'label' => __('services::common.labels.ranks'), 'count' => (string) Rank::query()->count()];

        return $sections;
    }

    public function render(): View
    {
        return view('structure::livewire.structure.services');
    }
}
