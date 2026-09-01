@php
    $contextTabs = ['overview', 'catalogs', 'matrix', 'profiles', 'planning', 'calendar', 'results', 'analytics', 'reports', 'lists'];
    $stats = $this->stats;
    $plan = $this->annualPlan;

    // Only the tabs whose row count is unambiguous carry a number, and only once there is
    // something to count — a column of zeros reads as broken, not as information.
    $tabCounts = array_filter([
        'planning' => (int) $stats['plan_items'],
        'calendar' => (int) $stats['sessions'],
        'results' => (int) $stats['deliveries'],
    ]);
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('training_needs::dashboard.panel.title')"
            :subtitle="$plan['title'] ?? null"
        >
            <x-context-panel.section :title="__('training_needs::dashboard.panel.sections')">
                @foreach ($contextTabs as $tab)
                    <x-context-panel.item
                        wire:key="training-panel-tab-{{ $tab }}"
                        wire:click.prevent="switchTab('{{ $tab }}')"
                        wire:loading.attr="disabled"
                        wire:target="switchTab"
                        :active="$activeTab === $tab"
                        :count="$tabCounts[$tab] ?? null"
                    >{{ __('training_needs::dashboard.tabs.'.$tab) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            @if ($plan)
                <x-context-panel.section :title="__('training_needs::dashboard.panel.annual_plan')" :padded="false">
                    <div class="px-3.5 pb-3.5 pt-1">
                        <p class="truncate text-[13px] font-semibold tracking-[-0.02em] text-ink">{{ $plan['title'] }}</p>
                        <p class="mt-0.5 text-[11px] text-ink-faint">
                            {{ __('training_needs::dashboard.plan_statuses.'.$plan['status']) }}
                            <span class="px-0.5">·</span>
                            <span class="hrm-num">{{ $plan['items'] }}</span> {{ __('training_needs::dashboard.panel.plan_items_unit') }}
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full bg-ink" style="width: {{ $plan['percent'] }}%"></div>
                            </div>
                            <span class="hrm-num shrink-0 text-[11px] font-semibold text-ink">{{ $plan['percent'] }}%</span>
                        </div>
                    </div>
                </x-context-panel.section>
            @endif
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('training_needs::dashboard.title')"
        :breadcrumb="__('training_needs::dashboard.panel.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$stats['needs']" :label="__('training_needs::dashboard.stats.needs')" />
            <x-page-header.stat :value="$stats['plan_items']" :label="__('training_needs::dashboard.stats.plan_items')" />
            <x-page-header.stat :value="$stats['sessions']" :label="__('training_needs::dashboard.stats.sessions')" />
        </x-slot:stats>

        <x-slot:actions>
            <x-pill-button wire:click.prevent="switchTab('calendar')" wire:loading.attr="disabled" wire:target="switchTab">
                {{ __('training_needs::dashboard.tabs.calendar') }}
            </x-pill-button>

            <x-pill-button :href="route('training-needs.print-summary')" target="_blank" :icon="true"
                title="{{ __('training_needs::dashboard.actions.open_print_summary') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            </x-pill-button>

            <x-pill-button variant="primary" wire:click.prevent="switchTab('profiles')" wire:loading.attr="disabled" wire:target="switchTab">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('training_needs::dashboard.title') }}
            </x-pill-button>
        </x-slot:actions>

        {{-- small-screen fallback for the panel's section list --}}
        <x-filter.nav wrap class="min-w-0 lg:hidden">
            @foreach ($contextTabs as $tab)
                <x-filter.item
                    wire:key="training-chip-{{ $tab }}"
                    wire:click.prevent="switchTab('{{ $tab }}')"
                    :active="$activeTab === $tab"
                >{{ __('training_needs::dashboard.tabs.'.$tab) }}</x-filter.item>
            @endforeach
        </x-filter.nav>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="px-4 py-4 sm:px-5">
        @if ($activeTab === 'overview')
            <livewire:training-needs.overview lazy />
        @endif

        @if (in_array($activeTab, ['catalogs', 'matrix', 'profiles'], true))
            <livewire:training-needs.foundation-workspace :tab="$activeTab" :key="'training-needs-foundation-'.$activeTab" lazy />
        @endif

        @if (in_array($activeTab, ['planning', 'calendar'], true))
            <livewire:training-needs.operations-workspace :tab="$activeTab" :key="'training-needs-operations-'.$activeTab" lazy />
        @endif

        @if ($activeTab === 'results')
            <livewire:training-needs.results-workspace :tab="$activeTab" :key="'training-needs-results-'.$activeTab" lazy />
        @endif

        @if ($activeTab === 'analytics')
            <livewire:training-needs.analytics lazy />
        @endif

        @if ($activeTab === 'reports')
            <livewire:training-needs.reports :key="'training-needs-reports-'.$reportsVersion" lazy />
        @endif

        @if ($activeTab === 'lists')
            <livewire:training-needs.lists lazy />
        @endif
    </div>

</div>
