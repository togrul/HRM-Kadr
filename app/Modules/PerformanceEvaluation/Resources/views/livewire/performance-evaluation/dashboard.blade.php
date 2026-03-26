<div class="flex flex-col space-y-4 px-6 py-4">
    <x-surface-card :title="__('performance_evaluation::dashboard.title')" icon="icons.performance-icon">
        <div class="space-y-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('performance_evaluation::dashboard.workspace.title') }}</p>
                    <p class="max-w-3xl text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.workspace.description') }}</p>
                    <div class="pt-2">
                        <a
                            href="{{ route('docs.guide', ['focus' => 'performance']) }}#performance-module"
                            class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:border-emerald-300 hover:bg-emerald-100"
                        >
                            {{ __('performance_evaluation::dashboard.actions.open_user_guide') }}
                        </a>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('performance_evaluation::dashboard.stats.cycles') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $this->stats['cycles'] }}</p>
                    </div>
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('performance_evaluation::dashboard.stats.templates') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $this->stats['templates'] }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('performance_evaluation::dashboard.stats.forms') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $this->stats['forms'] }}</p>
                    </div>
                    <div class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-violet-700">{{ __('performance_evaluation::dashboard.stats.links') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-violet-900">{{ $this->stats['links'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('performance_evaluation::dashboard.sections.title') }}</p>
                    <span class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.sections.description') }}</span>
                </div>

                <x-filter.nav class="min-w-0">
                    <x-filter.item wire:click.prevent="switchTab('overview')" :active="$activeTab === 'overview'">
                        {{ __('performance_evaluation::dashboard.tabs.overview') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('cycles')" :active="$activeTab === 'cycles'">
                        {{ __('performance_evaluation::dashboard.tabs.cycles') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('templates')" :active="$activeTab === 'templates'">
                        {{ __('performance_evaluation::dashboard.tabs.templates') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('evaluations')" :active="$activeTab === 'evaluations'">
                        {{ __('performance_evaluation::dashboard.tabs.evaluations') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('tests')" :active="$activeTab === 'tests'">
                        {{ __('performance_evaluation::dashboard.tabs.tests') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('reports')" :active="$activeTab === 'reports'">
                        {{ __('performance_evaluation::dashboard.tabs.reports') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('lists')" :active="$activeTab === 'lists'">
                        {{ __('performance_evaluation::dashboard.tabs.lists') }}
                    </x-filter.item>
                </x-filter.nav>
            </div>
        </div>
    </x-surface-card>

    @if ($activeTab === 'overview')
        <livewire:performance-evaluation.overview lazy />
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

    <x-ui.delete-confirmation-modal />
</div>
