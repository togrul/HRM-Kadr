<div class="space-y-4">
    <div class="grid gap-4 xl:grid-cols-3">
        <x-surface-card :title="__('training_needs::dashboard.cards.coverage_ratio')" icon="icons.profile-icon">
            <div class="grid gap-3">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.coverage_ratio') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->analyticsSummary['coverage_ratio'] }}%</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.mapping_ratio') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->analyticsSummary['mapping_ratio'] }}%</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.requirement_coverage_ratio') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->analyticsSummary['requirement_coverage_ratio'] }}%</p>
                </div>
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.reporting_summary')" icon="icons.pending-icon">
            <div class="space-y-3">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.total_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->analyticsSummary['total_needs'] }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.approved_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->analyticsSummary['approved_needs'] }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.planned_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->analyticsSummary['planned_needs'] }}</p>
                </div>
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.source_mix')" icon="icons.folder-plus-icon">
            <div class="space-y-3">
                @forelse ($this->analyticsSourceMix as $source => $count)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <span class="text-sm font-semibold text-zinc-900">{{ __('training_needs::dashboard.sources.'.$source) }}</span>
                        <x-small-badge mode="blue">{{ $count }}</x-small-badge>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">{{ __('training_needs::dashboard.empty.analytics') }}</p>
                @endforelse
            </div>
        </x-surface-card>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <x-surface-card :title="__('training_needs::dashboard.cards.priority_mix')" icon="icons.profile-outline-icon">
            <div class="space-y-3">
                @forelse ($this->analyticsPriorityMix as $priority => $count)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <span class="text-sm font-semibold text-zinc-900">{{ __('training_needs::dashboard.priorities.'.$priority) }}</span>
                        <x-small-badge :mode="$priority === 'high' ? 'red' : ($priority === 'medium' ? 'green' : 'secondary')">{{ $count }}</x-small-badge>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">{{ __('training_needs::dashboard.empty.analytics') }}</p>
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.top_gap_positions')" icon="icons.training-icon">
            <div class="space-y-3">
                @forelse ($this->topGapPositions as $position => $count)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <span class="text-sm font-semibold text-zinc-900">{{ $position }}</span>
                        <x-small-badge mode="red">{{ $count }}</x-small-badge>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">{{ __('training_needs::dashboard.empty.analytics') }}</p>
                @endforelse
            </div>
        </x-surface-card>
    </div>
</div>
