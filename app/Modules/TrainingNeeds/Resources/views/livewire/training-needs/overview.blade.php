<div class="space-y-4">
    <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
        <x-surface-card :title="__('training_needs::dashboard.cards.foundation_scope')" icon="icons.profile-outline-icon">
            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-sm font-semibold text-zinc-800">{{ __('training_needs::dashboard.cards.catalog_scope_title') }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('training_needs::dashboard.cards.catalog_scope_description') }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-sm font-semibold text-zinc-800">{{ __('training_needs::dashboard.cards.matrix_scope_title') }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('training_needs::dashboard.cards.matrix_scope_description') }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-sm font-semibold text-zinc-800">{{ __('training_needs::dashboard.cards.profile_scope_title') }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('training_needs::dashboard.cards.profile_scope_description') }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-sm font-semibold text-zinc-800">{{ __('training_needs::dashboard.cards.integration_scope_title') }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('training_needs::dashboard.cards.integration_scope_description') }}</p>
                </div>
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.next_sprints')" icon="icons.pending-icon">
            <div class="space-y-3">
                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
                    <p class="text-sm font-semibold text-sky-900">{{ __('training_needs::dashboard.cards.sprint_two_title') }}</p>
                    <p class="mt-1 text-sm text-sky-700">{{ __('training_needs::dashboard.cards.sprint_two_description') }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-sm font-semibold text-emerald-900">{{ __('training_needs::dashboard.cards.sprint_three_title') }}</p>
                    <p class="mt-1 text-sm text-emerald-700">{{ __('training_needs::dashboard.cards.sprint_three_description') }}</p>
                </div>
                <div class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3">
                    <p class="text-sm font-semibold text-violet-900">{{ __('training_needs::dashboard.cards.sprint_five_title') }}</p>
                    <p class="mt-1 text-sm text-violet-700">{{ __('training_needs::dashboard.cards.sprint_five_description') }}</p>
                </div>
            </div>
        </x-surface-card>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-surface-card :title="__('training_needs::dashboard.cards.recent_competencies')" icon="icons.folder-plus-icon">
            <div class="space-y-3">
                @forelse ($this->recentCompetencies as $competency)
                    <x-ui.list-card>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-zinc-900">{{ $competency->name }}</span>
                            @if ($competency->is_mandatory)
                                <x-small-badge mode="red">{{ __('training_needs::dashboard.labels.mandatory') }}</x-small-badge>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">{{ $competency->group?->name ?? __('training_needs::dashboard.labels.no_group') }}</p>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.profile-icon" :message="__('training_needs::dashboard.empty.recent_competencies')" />
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.recent_programs')" icon="icons.clock-icon">
            <div class="space-y-3">
                @forelse ($this->recentPrograms as $program)
                    <x-ui.list-card>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-zinc-900">{{ $program->title }}</span>
                            <x-small-badge mode="green">{{ __('training_needs::dashboard.delivery_types.'.$program->delivery_type) }}</x-small-badge>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ __('training_needs::dashboard.labels.program_meta', [
                                'code' => $program->code ?: __('training_needs::dashboard.labels.no_code'),
                                'hours' => $program->duration_hours ?: 0,
                            ]) }}
                        </p>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.training-icon" :message="__('training_needs::dashboard.empty.recent_programs')" />
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.coverage_snapshot')" icon="icons.profile-icon">
            <div class="grid gap-3">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.stats.program_maps') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->stats['program_maps'] }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.stats.profiles') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->stats['profiles'] }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.stats.needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->stats['needs'] }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.stats.plans') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->stats['plans'] }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.stats.plan_items') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->stats['plan_items'] }}</p>
                </div>
            </div>
        </x-surface-card>
    </div>
</div>
