@php
    $contextTabs = ['overview', 'goals', 'succession', 'feedback', 'cycles', 'templates', 'evaluations', 'tests', 'reports', 'lists'];
    $stats = $this->stats;
    $cycle = $this->activeCycle;

    // A number only where it is unambiguously that tab's row count, and only when non-zero.
    $tabCounts = array_filter([
        'cycles' => (int) $stats['cycles'],
        'templates' => (int) $stats['templates'],
        'evaluations' => (int) $stats['forms'],
    ]);
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('performance_evaluation::dashboard.panel.title')"
            :subtitle="$cycle['name'] ?? null"
        >
            <x-context-panel.section :title="__('performance_evaluation::dashboard.sections.title')">
                @foreach ($contextTabs as $tab)
                    <x-context-panel.item
                        wire:key="performance-panel-tab-{{ $tab }}"
                        wire:click.prevent="switchTab('{{ $tab }}')"
                        wire:loading.attr="disabled"
                        wire:target="switchTab"
                        :active="$activeTab === $tab"
                        :count="$tabCounts[$tab] ?? null"
                    >{{ __('performance_evaluation::dashboard.tabs.'.$tab) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            @if ($cycle)
                <x-context-panel.section :title="__('performance_evaluation::dashboard.panel.active_cycle')" :padded="false">
                    <div class="px-3.5 pb-3.5 pt-1">
                        <p class="truncate text-[13px] font-semibold tracking-[-0.02em] text-ink">{{ $cycle['name'] }}</p>
                        <p class="hrm-num mt-0.5 text-[11px] text-ink-faint">{{ $cycle['period'] }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full bg-ink" style="width: {{ $cycle['percent'] }}%"></div>
                            </div>
                            <span class="hrm-num shrink-0 text-[11px] font-semibold text-ink">{{ $cycle['percent'] }}%</span>
                        </div>
                        <p class="mt-1.5 text-[11px] text-ink-faint">
                            {{ __('performance_evaluation::dashboard.panel.cycle_progress_note', ['scored' => $cycle['scored'], 'total' => $cycle['forms']]) }}
                        </p>
                    </div>
                </x-context-panel.section>
            @endif
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('performance_evaluation::dashboard.title')"
        :breadcrumb="__('performance_evaluation::dashboard.panel.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$stats['forms']" :label="__('performance_evaluation::dashboard.stats.forms')" />
            <x-page-header.stat :value="$this->scoreDistribution['average']" :label="__('performance_evaluation::dashboard.stats.scores')" />
            <x-page-header.stat :value="$stats['links']" :label="__('performance_evaluation::dashboard.stats.links')" tone="amber" />
        </x-slot:stats>

        <x-slot:actions>
            <x-pill-button wire:click.prevent="switchTab('templates')" wire:loading.attr="disabled" wire:target="switchTab">
                {{ __('performance_evaluation::dashboard.panel.new_template') }}
            </x-pill-button>

            <x-pill-button :href="route('performance-evaluation.print-summary')" target="_blank" :icon="true"
                title="{{ __('performance_evaluation::dashboard.actions.open_print_summary') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            </x-pill-button>

            <x-pill-button variant="primary" wire:click.prevent="switchTab('evaluations')" wire:loading.attr="disabled" wire:target="switchTab">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('performance_evaluation::dashboard.panel.assign_form') }}
            </x-pill-button>
        </x-slot:actions>

        {{-- small-screen fallback for the panel's section list --}}
        <x-filter.nav wrap class="min-w-0 lg:hidden">
            @foreach ($contextTabs as $tab)
                <x-filter.item
                    wire:key="performance-chip-{{ $tab }}"
                    wire:click.prevent="switchTab('{{ $tab }}')"
                    :active="$activeTab === $tab"
                >{{ __('performance_evaluation::dashboard.tabs.'.$tab) }}</x-filter.item>
            @endforeach
        </x-filter.nav>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="px-4 py-4 sm:px-5">
        @if ($activeTab === 'overview')
            <livewire:performance-evaluation.overview lazy />
        @endif

        @if ($activeTab === 'goals')
            <livewire:performance-evaluation.goals-workspace lazy />
        @endif

        @if ($activeTab === 'succession')
            <livewire:performance-evaluation.succession-workspace lazy />
        @endif

        @if ($activeTab === 'feedback')
            <livewire:performance-evaluation.feedback-360-workspace lazy />
        @endif

        @if (in_array($activeTab, ['cycles', 'templates'], true))
            <livewire:performance-evaluation.foundation-workspace :tab="$activeTab" :key="'performance-evaluation-foundation-'.$activeTab" lazy />
        @endif

        @if (in_array($activeTab, ['evaluations', 'tests'], true))
            <livewire:performance-evaluation.operations-workspace :tab="$activeTab" :tests-view="request()->query('tests_view')" :key="'performance-evaluation-operations-'.$activeTab.'-'.request()->query('tests_view', 'banks')" lazy />
        @endif

        @if ($activeTab === 'reports')
            <livewire:performance-evaluation.reports lazy />
        @endif

        @if ($activeTab === 'lists')
            <livewire:performance-evaluation.lists lazy />
        @endif
    </div>
</div>
