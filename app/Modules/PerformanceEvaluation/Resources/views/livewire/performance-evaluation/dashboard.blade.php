@php
    $contextTabs = ['overview', 'kpi_scorecards', 'kpi_library', 'goals', 'succession', 'feedback', 'cycles', 'templates', 'evaluations', 'tests', 'reports', 'lists'];
    // The panel groups the module's twelve screens by job, so the list reads as a map, not a wall.
    $navGroups = [
        'home' => ['overview'],
        'kpi' => ['kpi_scorecards', 'kpi_library'],
        'evaluation' => ['cycles', 'templates', 'evaluations', 'tests'],
        'talent' => ['goals', 'succession', 'feedback'],
        'insight' => ['reports', 'lists'],
    ];
    $navIcons = [
        'overview' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'kpi_scorecards' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 15l2.5-3 2.5 2 3-4"/>',
        'kpi_library' => '<path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2z"/><path d="M4 19V5M9 7h6"/>',
        'cycles' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'templates' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'evaluations' => '<path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/>',
        'tests' => '<path d="M9 3h6M10 3v6L5 19a1.5 1.5 0 0 0 1.3 2h11.4a1.5 1.5 0 0 0 1.3-2l-5-10V3"/>',
        'goals' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'succession' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'feedback' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'reports' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
        'lists' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    ];
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
            @foreach ($navGroups as $group => $groupTabs)
                @continue(array_intersect($groupTabs, $tabs) === [])
                <x-context-panel.section :title="$group === 'home' ? null : __('performance_evaluation::dashboard.nav_groups.'.$group)">
                    @foreach (array_intersect($groupTabs, $tabs) as $tab)
                        <x-context-panel.item
                            wire:key="performance-panel-tab-{{ $tab }}"
                            wire:click.prevent="switchTab('{{ $tab }}')"
                            wire:loading.attr="disabled"
                            wire:target="switchTab"
                            :active="$activeTab === $tab"
                            :count="$tabCounts[$tab] ?? null"
                        >
                            <x-slot:icon>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $navIcons[$tab] ?? '<circle cx="12" cy="12" r="3"/>' !!}</svg>
                            </x-slot:icon>
                            {{ __('performance_evaluation::dashboard.tabs.'.$tab) }}
                        </x-context-panel.item>
                    @endforeach
                </x-context-panel.section>
            @endforeach

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
            <x-pill-button variant="secondary" :href="route('docs.guide', ['focus' => 'performance']).'#performance-module'">
                {{ __('performance_evaluation::dashboard.actions.open_user_guide') }}
            </x-pill-button>

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

        @if ($activeTab === 'kpi_scorecards')
            <livewire:performance-evaluation.kpi-scorecards lazy />
        @endif

        @if ($activeTab === 'kpi_library')
            <livewire:performance-evaluation.kpi-library lazy />
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
