@php
    $stats = $this->stats;

    $metrics = [
        ['label' => __('training_needs::dashboard.stats.competencies'), 'value' => $stats['competencies'], 'dot' => 'bg-[#0284c7]'],
        ['label' => __('training_needs::dashboard.stats.programs'), 'value' => $stats['programs'], 'dot' => 'bg-[#f59e0b]'],
        ['label' => __('training_needs::dashboard.stats.requirements'), 'value' => $stats['requirements'], 'dot' => 'bg-[#7c3aed]'],
        ['label' => __('training_needs::dashboard.stats.needs'), 'value' => $stats['needs'], 'dot' => 'bg-[#e11d48]'],
        ['label' => __('training_needs::dashboard.stats.plan_items'), 'value' => $stats['plan_items'], 'dot' => 'bg-[#059669]'],
        ['label' => __('training_needs::dashboard.stats.sessions'), 'value' => $stats['sessions'], 'dot' => 'bg-[#a1a1aa]'],
    ];

    $priorityTone = fn (?string $priority): string => match ($priority) {
        'high' => 'rose',
        'medium' => 'amber',
        default => 'secondary',
    };
@endphp

<div class="flex flex-col gap-4">
    <section class="rounded-xl border border-hairline bg-white">
        <div class="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($metrics as $metric)
                <div class="rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="hrm-eyebrow">{{ $metric['label'] }}</span>
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $metric['dot'] }}"></span>
                    </div>
                    <p class="hrm-num mt-1.5 text-[22px] font-semibold tracking-[-0.03em] text-ink">{{ $metric['value'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===================== training needs ===================== --}}
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('training_needs::dashboard.panel.needs_title') }}</h2>
            <p class="mt-0.5 text-[12px] text-ink-faint">{{ __('training_needs::dashboard.panel.needs_description') }}</p>
        </div>

        <x-table.tbl :headers="[
            __('training_needs::dashboard.fields.personnel'),
            __('training_needs::dashboard.fields.competency'),
            __('training_needs::dashboard.fields.priority'),
            __('training_needs::dashboard.fields.recommended_program'),
            __('training_needs::dashboard.fields.status'),
        ]">
            @forelse ($this->recentNeeds as $need)
                <tr wire:key="training-need-{{ $need->id }}">
                    <x-table.td standart-width>
                        <div class="flex items-center gap-2.5">
                            <x-avatar :name="(string) $need->personnel?->fullname" :tone="$need->priority === 'high' ? 'rose' : 'neutral'" />
                            <div class="min-w-0 max-w-[220px] leading-tight">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $need->personnel?->fullname ?? '—' }}</p>
                                <p class="hrm-num truncate text-[11px] text-ink-faint">{{ $need->personnel?->tabel_no }}</p>
                            </div>
                        </div>
                    </x-table.td>

                    <x-table.td standart-width>
                        <p class="max-w-[200px] truncate text-[13px] text-ink-soft">{{ $need->competency?->name ?? '—' }}</p>
                    </x-table.td>

                    <x-table.td>
                        <x-small-badge :mode="$priorityTone($need->priority)">
                            {{ __('training_needs::dashboard.priorities.'.($need->priority ?: 'low')) }}
                        </x-small-badge>
                    </x-table.td>

                    <x-table.td standart-width>
                        <p class="max-w-[220px] truncate text-[13px] text-ink-soft">
                            {{ $need->recommendedProgram?->title ?: __('training_needs::dashboard.labels.no_program') }}
                        </p>
                    </x-table.td>

                    <x-table.td>
                        <x-small-badge mode="secondary" dot>
                            {{ __('training_needs::dashboard.need_statuses.'.($need->status ?: 'draft')) }}
                        </x-small-badge>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6">
                        <x-ui.empty-state icon="icons.training-icon" :message="__('training_needs::dashboard.empty.recent_needs')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>
    </section>

    {{-- ===================== catalog freshness ===================== --}}
    <div class="grid gap-4 xl:grid-cols-2">
        <section class="rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <p class="hrm-eyebrow">{{ __('training_needs::dashboard.cards.recent_competencies') }}</p>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentCompetencies as $competency)
                    <div wire:key="training-competency-{{ $competency->id }}" class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0 leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $competency->name }}</p>
                            <p class="truncate text-[11.5px] text-ink-faint">{{ $competency->group?->name ?? __('training_needs::dashboard.labels.no_group') }}</p>
                        </div>
                        @if ($competency->is_mandatory)
                            <x-small-badge mode="rose">{{ __('training_needs::dashboard.labels.mandatory') }}</x-small-badge>
                        @endif
                    </div>
                @empty
                    <div class="p-3">
                        <x-ui.empty-state icon="icons.profile-icon" :message="__('training_needs::dashboard.empty.recent_competencies')" />
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <p class="hrm-eyebrow">{{ __('training_needs::dashboard.cards.recent_programs') }}</p>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentPrograms as $program)
                    <div wire:key="training-program-{{ $program->id }}" class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0 leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $program->title }}</p>
                            <p class="truncate text-[11.5px] text-ink-faint">
                                {{ __('training_needs::dashboard.labels.program_meta', [
                                    'code' => $program->code ?: __('training_needs::dashboard.labels.no_code'),
                                    'hours' => $program->duration_hours ?: 0,
                                ]) }}
                            </p>
                        </div>
                        <x-small-badge mode="green">{{ __('training_needs::dashboard.delivery_types.'.$program->delivery_type) }}</x-small-badge>
                    </div>
                @empty
                    <div class="p-3">
                        <x-ui.empty-state icon="icons.training-icon" :message="__('training_needs::dashboard.empty.recent_programs')" />
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
