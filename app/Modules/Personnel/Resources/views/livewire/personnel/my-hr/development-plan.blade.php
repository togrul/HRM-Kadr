@php
    $payload = $this->payload;
    $summary = $payload['summary'];
    $rows = $payload['rows'];

    $label = 'hrm-eyebrow block pb-1';

    $metrics = [
        ['label' => __('personnel::my_hr.development_plan.summary.total'), 'value' => $summary['total'], 'dot' => 'bg-[#a1a1aa]'],
        ['label' => __('personnel::my_hr.development_plan.summary.planned'), 'value' => $summary['planned'], 'dot' => 'bg-[#0284c7]'],
        ['label' => __('personnel::my_hr.development_plan.summary.completed_sessions'), 'value' => $summary['completed'], 'dot' => 'bg-[#059669]'],
        ['label' => __('personnel::my_hr.development_plan.summary.completed_needs'), 'value' => $summary['needs_completed'], 'dot' => 'bg-[#7c3aed]'],
    ];

    $tone = fn (?string $mode): string => match ($mode) {
        'success' => 'green',
        'warning' => 'amber',
        'info' => 'blue',
        'danger' => 'rose',
        default => 'secondary',
    };
@endphp

<div class="flex flex-col gap-4">
    <section class="rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <p class="hrm-eyebrow">{{ __('personnel::my_hr.development_plan.kicker') }}</p>
            <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-ink-muted">{{ __('personnel::my_hr.development_plan.description') }}</p>
        </div>

        @include('personnel::livewire.personnel.my-hr.partials.metric-strip', ['metrics' => $metrics])

        <div class="grid gap-3 border-t border-hairline-subtle p-3 lg:grid-cols-2">
            <label class="min-w-0">
                <span class="{{ $label }}">{{ __('personnel::my_hr.development_plan.fields.search') }}</span>
                <x-ui.input wire:model.live.debounce.300ms="search" type="search" icon="search"
                    placeholder="{{ __('personnel::my_hr.development_plan.messages.search_placeholder') }}" />
            </label>

            <label class="min-w-0">
                <span class="{{ $label }}">{{ __('personnel::my_hr.development_plan.fields.status') }}</span>
                <x-ui.select wire:model.live="statusFilter">
                    <option value="all">{{ __('personnel::my_hr.development_plan.filters.all') }}</option>
                    @foreach (['draft', 'review', 'approved', 'planned', 'completed'] as $status)
                        <option value="{{ $status }}">{{ __('training_needs::dashboard.need_statuses.'.$status) }}</option>
                    @endforeach
                </x-ui.select>
            </label>
        </div>
    </section>

    @forelse ($rows as $row)
        <section wire:key="my-hr-need-{{ $loop->index }}" class="rounded-xl border border-hairline bg-white">
            <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h3 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ $row['title'] }}</h3>
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                        <x-small-badge :mode="$tone($row['status_mode'])" dot>{{ $row['status_label'] }}</x-small-badge>
                        <x-small-badge :mode="$tone($row['priority_mode'])">{{ $row['priority_label'] }}</x-small-badge>
                        <x-small-badge mode="secondary">{{ $row['source_label'] }}</x-small-badge>
                    </div>
                </div>

                <span class="hrm-num shrink-0 rounded-full border border-hairline bg-[#fafafa] px-2.5 py-1 text-[11.5px] font-medium text-ink-soft">
                    {{ $row['target_date_badge'] }}
                </span>
            </div>

            <div class="space-y-3 p-3">
                <p class="text-[12.5px] leading-relaxed text-ink-muted">{{ $row['summary'] }}</p>

                @if ($row['plan_note'])
                    <div class="rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3">
                        <p class="hrm-eyebrow">{{ __('personnel::my_hr.development_plan.labels.plan_note') }}</p>
                        <p class="mt-1.5 text-[12.5px] leading-relaxed text-ink-soft">{{ $row['plan_note'] }}</p>
                    </div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($row['details'] as $detail)
                        <x-fact-tile :label="$detail['label']" :value="$detail['value']" />
                    @endforeach
                </div>

                <div class="rounded-xl border border-hairline bg-[#fafafa] p-3">
                    <div class="flex items-center justify-between gap-3 pb-2">
                        <p class="hrm-eyebrow">{{ __('personnel::my_hr.development_plan.labels.sessions') }}</p>
                        <span class="hrm-num text-[11.5px] text-ink-faint">{{ count($row['sessions']) }}</span>
                    </div>

                    @if ($row['sessions'] !== [])
                        <div class="grid gap-3 xl:grid-cols-2">
                            @foreach ($row['sessions'] as $session)
                                <div class="rounded-xl border border-hairline bg-white px-3.5 py-3">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 leading-tight">
                                            <p class="truncate text-[13px] font-medium text-ink">{{ $session['title'] }}</p>
                                            @if ($session['program'])
                                                <p class="mt-0.5 truncate text-[11.5px] text-ink-faint">{{ $session['program'] }}</p>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                                            <x-small-badge :mode="$tone($session['attendance_status_mode'])" dot>{{ $session['attendance_status_label'] }}</x-small-badge>
                                            <x-small-badge :mode="$tone($session['session_status_mode'])">{{ $session['session_status_label'] }}</x-small-badge>
                                        </div>
                                    </div>

                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <x-fact-tile :label="__('personnel::my_hr.development_plan.labels.session_window')" :value="$session['window']" />
                                        <x-fact-tile :label="__('personnel::my_hr.development_plan.labels.location')" :value="$session['location']" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state icon="icons.calendar-icon" :message="__('personnel::my_hr.development_plan.messages.no_sessions')" />
                    @endif
                </div>
            </div>
        </section>
    @empty
        <x-ui.empty-state icon="icons.training-icon" :title="__('personnel::my_hr.development_plan.empty.title')" :message="__('personnel::my_hr.development_plan.empty.body')" />
    @endforelse
</div>
