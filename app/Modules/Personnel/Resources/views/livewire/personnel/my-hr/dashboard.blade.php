@php
    $counts = $this->panelCounts;
    $balance = $this->vacationBalance;
    $tabLabel = fn (string $tab): string => __('personnel::my_hr.tabs.'.str_replace('-', '_', $tab));
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    @if ($this->hasPersonnelLink)
        <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

        @teleport('#hrm-context-panel')
            <x-context-panel
                :title="__('personnel::my_hr.title')"
                :subtitle="$this->personnel?->fullname"
            >
                <x-context-panel.section>
                    @foreach ($this->tabs() as $tab)
                        <x-context-panel.item
                            wire:key="my-hr-panel-tab-{{ $tab }}"
                            wire:click.prevent="setActiveTab('{{ $tab }}')"
                            wire:loading.attr="disabled"
                            wire:target="setActiveTab"
                            :active="$activeTab === $tab"
                            :count="($counts[$tab] ?? 0) > 0 ? $counts[$tab] : null"
                        >{{ $tabLabel($tab) }}</x-context-panel.item>
                    @endforeach
                </x-context-panel.section>

                @if ($balance)
                    @php $usedPercent = $balance['total'] > 0 ? round($balance['used'] / $balance['total'] * 100) : 0; @endphp
                    <x-context-panel.section :title="__('personnel::my_hr.balance.title')" :padded="false">
                        <div class="px-3.5 pb-3.5 pt-1">
                            <div class="flex items-baseline gap-1.5">
                                <span class="hrm-num text-[26px] font-semibold leading-none tracking-[-0.02em] text-ink">{{ $balance['remaining'] }}</span>
                                <span class="text-[11.5px] text-ink-faint">{{ __('personnel::my_hr.balance.remaining_note', ['total' => $balance['total']]) }}</span>
                            </div>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full bg-ink" style="width: {{ $usedPercent }}%"></div>
                            </div>
                            <p class="mt-1.5 text-[11px] text-ink-faint">{{ __('personnel::my_hr.balance.used', ['count' => $balance['used']]) }}</p>
                        </div>
                    </x-context-panel.section>
                @endif
            </x-context-panel>
        @endteleport
    @endif

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('personnel::my_hr.title')"
        :breadcrumb="$this->hasPersonnelLink ? $tabLabel($activeTab) : __('personnel::my_hr.title')"
        :breadcrumb-root="__('personnel::my_hr.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-pill-button :href="route('docs.guide', ['focus' => 'my-hr']).'#my-hr-module'">
                {{ __('personnel::my_hr.actions.open_docs') }}
            </x-pill-button>

            @if ($this->hasPersonnelLink && $this->createForms !== [])
                {{-- "Yeni ərizə" has to pick a type: the tab holds three different forms. --}}
                <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                    <x-pill-button variant="primary" x-on:click="open = ! open" ::aria-expanded="open">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('personnel::my_hr.actions.new_request') }}
                    </x-pill-button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition.opacity
                        class="absolute right-0 z-30 mt-1.5 w-56 rounded-xl border border-hairline bg-white p-1 shadow-overlay"
                    >
                        @foreach ($this->createForms as $form)
                            <button
                                type="button"
                                wire:key="my-hr-new-request-{{ $form }}"
                                wire:click="goto('requests', '{{ $form }}')"
                                wire:loading.attr="disabled"
                                wire:target="goto"
                                x-on:click="open = false"
                                class="flex w-full items-center rounded-lg px-2.5 py-2 text-left text-[12.5px] font-medium text-ink-soft transition hover:bg-[#fafafa] hover:text-ink"
                            >{{ __('personnel::my_hr.requests.actions.create_'.$form) }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-slot:actions>

        @if ($this->hasPersonnelLink)
            {{-- small-screen fallback for the panel's tab list --}}
            <x-filter.nav wrap class="min-w-0 lg:hidden">
                @foreach ($this->tabs() as $tab)
                    <x-filter.item
                        wire:key="my-hr-chip-{{ $tab }}"
                        wire:click.prevent="setActiveTab('{{ $tab }}')"
                        :active="$activeTab === $tab"
                    >{{ $tabLabel($tab) }}</x-filter.item>
                @endforeach
            </x-filter.nav>
        @endif
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="px-4 py-4 sm:px-5">
        @if (! $this->hasPersonnelLink)
            <div class="rounded-xl border border-[#fde68a] bg-[#fffbeb] px-4 py-4">
                <p class="hrm-eyebrow text-[#b45309]">{{ __('personnel::my_hr.empty_state.kicker') }}</p>
                <h2 class="mt-1.5 text-[15px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::my_hr.empty_state.title') }}</h2>
                <p class="mt-1 max-w-3xl text-[12.5px] leading-relaxed text-ink-muted">{{ __('personnel::my_hr.empty_state.body') }}</p>
                <p class="mt-3 rounded-xl border border-hairline bg-white px-3.5 py-2.5 text-[12.5px] text-ink-soft">{{ __('personnel::my_hr.empty_state.hint') }}</p>
            </div>
        @elseif ($activeTab === 'overview')
            <livewire:personnel.my-hr.summary :personnel-id="$personnelId" :key="'my-hr-summary-'.$personnelId" />
        @elseif ($activeTab === 'requests')
            <livewire:personnel.my-hr.requests :personnel-id="$personnelId" :open-form="$pendingRequestForm" :key="'my-hr-requests-'.$personnelId.'-'.($pendingRequestForm ?: 'none')" />
        @elseif ($activeTab === 'notifications')
            <livewire:personnel.my-hr.notifications :personnel-id="$personnelId" :key="'my-hr-notifications-'.$personnelId" />
        @elseif ($activeTab === 'onboarding')
            <livewire:personnel.my-hr.onboarding :personnel-id="$personnelId" :key="'my-hr-onboarding-'.$personnelId" />
        @elseif ($activeTab === 'development-plan')
            <livewire:personnel.my-hr.development-plan :personnel-id="$personnelId" :key="'my-hr-development-plan-'.$personnelId" />
        @elseif ($activeTab === 'learning')
            <livewire:personnel.my-hr.learning :personnel-id="$personnelId" :key="'my-hr-learning-'.$personnelId" />
        @elseif ($activeTab === 'documents')
            <livewire:personnel.my-hr.documents :personnel-id="$personnelId" :key="'my-hr-documents-'.$personnelId" />
        @elseif ($activeTab === 'payslips')
            <livewire:personnel.my-hr.payslips :personnel-id="$personnelId" :key="'my-hr-payslips-'.$personnelId" />
        @elseif ($activeTab === 'hierarchy')
            <livewire:personnel.my-hr.hierarchy :personnel-id="$personnelId" :key="'my-hr-hierarchy-'.$personnelId" />
        @endif
    </div>
</div>
