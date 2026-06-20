<div class="flex flex-col space-y-4 px-6 py-4">
    <x-surface-card :title="__('training_needs::dashboard.title')" icon="icons.folder-plus-icon">
        <div class="space-y-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.workspace.title') }}</p>
                    <p class="max-w-3xl text-sm text-zinc-500">{{ __('training_needs::dashboard.workspace.description') }}</p>
                    <div class="pt-2">
                        <a
                            href="{{ route('docs.guide', ['focus' => 'training']) }}#training-module"
                            class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:border-sky-300 hover:bg-sky-100"
                        >
                            {{ __('training_needs::dashboard.actions.open_user_guide') }}
                        </a>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('training_needs::dashboard.stats.groups') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $this->stats['groups'] }}</p>
                    </div>
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('training_needs::dashboard.stats.competencies') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $this->stats['competencies'] }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('training_needs::dashboard.stats.programs') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $this->stats['programs'] }}</p>
                    </div>
                    <div class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-violet-700">{{ __('training_needs::dashboard.stats.requirements') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-violet-900">{{ $this->stats['requirements'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.sections.title') }}</p>
                    <span class="text-xs text-zinc-500">{{ __('training_needs::dashboard.sections.description') }}</span>
                </div>

                <x-filter.nav class="min-w-0">
                    <x-filter.item wire:click.prevent="switchTab('overview')" :active="$activeTab === 'overview'">
                        {{ __('training_needs::dashboard.tabs.overview') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('catalogs')" :active="$activeTab === 'catalogs'">
                        {{ __('training_needs::dashboard.tabs.catalogs') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('matrix')" :active="$activeTab === 'matrix'">
                        {{ __('training_needs::dashboard.tabs.matrix') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('profiles')" :active="$activeTab === 'profiles'">
                        {{ __('training_needs::dashboard.tabs.profiles') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('planning')" :active="$activeTab === 'planning'">
                        {{ __('training_needs::dashboard.tabs.planning') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('calendar')" :active="$activeTab === 'calendar'">
                        {{ __('training_needs::dashboard.tabs.calendar') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('results')" :active="$activeTab === 'results'">
                        {{ __('training_needs::dashboard.tabs.results') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('analytics')" :active="$activeTab === 'analytics'">
                        {{ __('training_needs::dashboard.tabs.analytics') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('reports')" :active="$activeTab === 'reports'">
                        {{ __('training_needs::dashboard.tabs.reports') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('lists')" :active="$activeTab === 'lists'">
                        {{ __('training_needs::dashboard.tabs.lists') }}
                    </x-filter.item>
                </x-filter.nav>
            </div>
        </div>
    </x-surface-card>

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

    <x-ui.delete-confirmation-modal />
</div>
