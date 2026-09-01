@php
    $sectionTitles = [
        'general' => __('services::common.labels.general'),
        'candidate' => __('services::common.labels.candidate_preferences'),
        'notifications-settings' => __('services::common.labels.notifications'),
        'menus' => __('services::common.labels.menus'),
        'roles' => __('services::common.navigation.roles_and_permissions'),
        'users' => __('services::common.labels.users'),
        'ranks' => __('services::common.labels.ranks'),
    ];
@endphp

<div x-data class="flex flex-col">

    {{-- ===================== contextual panel ===================== --}}
    {{--
        Rendered straight into the slot (no teleport): the panel body is its own Livewire
        component, so it keeps working outside the page component's root — and a nested
        Livewire component inside a teleport does not get its DOM patched on update.
    --}}
    <x-slot name="sidebar">
        <x-context-panel
            :title="__('ui::menu.items.settings')"
            :subtitle="__('services::settings.labels.system_configuration')"
        >
            <x-context-panel.section>
                @livewire('structure.services', key('structure'))
            </x-context-panel.section>

            <x-slot:footer>
                <p class="text-[11.5px] text-ink-faint">{{ __('services::common.messages.customize_settings') }}</p>
            </x-slot:footer>
        </x-context-panel>
    </x-slot>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('ui::menu.items.settings')"
        :breadcrumb="$sectionTitles[$selectedService] ?? __('ui::menu.items.settings')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </x-slot:icon>
    </x-page-header>

    {{-- the settings menu stays inline on small screens, where there is no side panel --}}
    <div class="border-b border-hairline bg-white px-2 py-2 lg:hidden">
        @livewire('structure.services', key('structure-compact'))
    </div>

    {{-- ===================== body ===================== --}}
    <div class="px-4 py-4 sm:px-5">
        <div wire:loading wire:target="selectService" class='text-input__loading'>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
        </div>

        @if (!$selectedService)
            <section class="rounded-xl border border-hairline bg-white px-4 py-6">
                <x-ui.empty-state icon="icons.settings2-icon" :title="__('services::common.messages.customize_settings')" />
            </section>
        @else
            <section wire:target="selectService" wire:loading.remove>
                @switch($selectedService)
                    @case('general')
                        @livewire('services.settings.settings-list', ['section' => 'general'], key('settings-general'))
                    @break

                    @case('candidate')
                        @livewire('services.settings.settings-list', ['section' => 'candidate'], key('settings-candidate'))
                    @break

                    @case('notifications-settings')
                        @livewire('notification.settings-hub', key('notifications-settings-hub'))
                    @break

                    @case('menus')
                        @livewire('services.menus.all-menus', key('menus'))
                    @break

                    @case('roles')
                        <div class="space-y-4" x-data="{ activeRoleTab: 'roles' }">
                            @php
                                $roleTab = 'h-[30px] shrink-0 rounded-[9px] border px-2.5 text-[12px] transition';
                                $roleTabOn = 'border-ink bg-ink font-semibold text-white';
                                $roleTabOff = 'border-hairline bg-[#f4f4f5] font-medium text-[#3f3f46] hover:bg-[#e4e4e7] hover:text-ink';
                            @endphp
                            <div class="flex items-center gap-1.5">
                                <button type="button" class="{{ $roleTab }}" x-on:click="activeRoleTab = 'roles'" x-bind:class="activeRoleTab === 'roles' ? '{{ $roleTabOn }}' : '{{ $roleTabOff }}'">
                                    {{ __('services::common.labels.roles') }}
                                </button>
                                <button type="button" class="{{ $roleTab }}" x-on:click="activeRoleTab = 'permissions'" x-bind:class="activeRoleTab === 'permissions' ? '{{ $roleTabOn }}' : '{{ $roleTabOff }}'">
                                    {{ __('services::common.labels.permissions') }}
                                </button>
                            </div>

                            <div x-show="activeRoleTab === 'roles'" x-cloak wire:key="roles-section">
                                @livewire('services.roles.manage-roles', key('roles'))
                            </div>

                            <div x-show="activeRoleTab === 'permissions'" x-cloak wire:key="permission-section">
                                @livewire('services.roles.permissions', key('permissions'))
                            </div>
                        </div>
                    @break

                    @case('users')
                        @livewire('services.users.all-users', key('users'))
                    @break

                    @case('ranks')
                        @livewire('services.ranks.all-ranks', key('ranks'))
                    @break
                @endswitch
            </section>
        @endif
    </div>
</div>
