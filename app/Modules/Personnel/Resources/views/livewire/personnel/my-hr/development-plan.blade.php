@php
    $payload = $this->payload;
    $summary = $payload['summary'];
    $rows = $payload['rows'];
    $statCards = [
        ['label' => __('personnel::my_hr.development_plan.summary.total'), 'value' => $summary['total']],
        ['label' => __('personnel::my_hr.development_plan.summary.planned'), 'value' => $summary['planned']],
        ['label' => __('personnel::my_hr.development_plan.summary.completed_sessions'), 'value' => $summary['completed']],
        ['label' => __('personnel::my_hr.development_plan.summary.completed_needs'), 'value' => $summary['needs_completed']],
    ];

    $toneClasses = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'info' => 'border-sky-200 bg-sky-50 text-sky-700',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
        'neutral' => 'border-zinc-200 bg-white text-zinc-700',
    ];
@endphp

<div class="space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.development_plan.kicker') }}</x-ui.field-label>
            <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.development_plan.title') }}</h2>
            <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.development_plan.description') }}</p>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($statCards as $card)
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ $card['label'] }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50/60 p-5 shadow-sm">
        <div class="grid gap-1 lg:grid-cols-2 2xl:grid-cols-4">
            <x-ui.input-shell :label="__('personnel::my_hr.development_plan.fields.search')" labelClass="tracking-tight text-zinc-500">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('personnel::my_hr.development_plan.messages.search_placeholder') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none" />
            </x-ui.input-shell>
            <x-ui.input-shell :label="__('personnel::my_hr.development_plan.fields.status')" labelClass="tracking-tight text-zinc-500">
                <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                    <option value="all">{{ __('personnel::my_hr.development_plan.filters.all') }}</option>
                    @foreach (['draft', 'review', 'approved', 'planned', 'completed'] as $status)
                        <option value="{{ $status }}">{{ __('training_needs::dashboard.need_statuses.'.$status) }}</option>
                    @endforeach
                </select>
            </x-ui.input-shell>
        </div>

        <div class="mt-6 space-y-4">
            @forelse ($rows as $row)
                <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1 space-y-3">
                            <div class="inline-flex max-w-full rounded-[24px] border border-zinc-200 bg-zinc-50 px-5 py-3">
                                <h3 class="max-w-[38rem] text-lg font-semibold tracking-tight text-zinc-950">{{ $row['title'] }}</h3>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold tracking-tight {{ $toneClasses[$row['status_mode']] ?? $toneClasses['neutral'] }}">{{ $row['status_label'] }}</span>
                                <span class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold tracking-tight {{ $toneClasses[$row['priority_mode']] ?? $toneClasses['neutral'] }}">{{ $row['priority_label'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold tracking-tight text-sky-700">{{ $row['source_label'] }}</span>
                            </div>
                        </div>

                        <div class="rounded-full border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-semibold tracking-tight text-zinc-600">
                            {{ $row['target_date_badge'] }}
                        </div>
                    </div>

                    <div class="mt-4 rounded-[24px] border border-zinc-200 bg-zinc-50/80 px-5 py-4 text-base leading-7 text-zinc-700">
                        {{ $row['summary'] }}
                    </div>

                    @if ($row['plan_note'])
                        <div class="mt-4 rounded-[24px] border border-zinc-200 bg-white px-5 py-4 text-sm leading-7 text-zinc-700">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.development_plan.labels.plan_note') }}</x-ui.field-label>
                            <p class="mt-2 font-medium text-zinc-800">{{ $row['plan_note'] }}</p>
                        </div>
                    @endif

                    <div class="mt-4 grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                        @foreach ($row['details'] as $detail)
                            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ $detail['label'] }}</x-ui.field-label>
                                <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $detail['value'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 rounded-[24px] border border-zinc-200 bg-zinc-50/80 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.development_plan.labels.sessions') }}</x-ui.field-label>
                            <span class="text-xs font-medium text-zinc-500">{{ count($row['sessions']) }}</span>
                        </div>

                        @if ($row['sessions'] !== [])
                            <div class="mt-3 grid gap-3 xl:grid-cols-2">
                                @foreach ($row['sessions'] as $session)
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="space-y-2">
                                                <p class="text-sm font-semibold text-zinc-950">{{ $session['title'] }}</p>
                                                @if ($session['program'])
                                                    <p class="text-xs text-zinc-500">{{ $session['program'] }}</p>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold tracking-tight {{ $toneClasses[$session['attendance_status_mode']] ?? $toneClasses['neutral'] }}">{{ $session['attendance_status_label'] }}</span>
                                                <span class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold tracking-tight {{ $toneClasses[$session['session_status_mode']] ?? $toneClasses['neutral'] }}">{{ $session['session_status_label'] }}</span>
                                            </div>
                                        </div>
                                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.development_plan.labels.session_window') }}</x-ui.field-label>
                                                <p class="mt-2 text-sm font-semibold text-zinc-900">{{ $session['window'] }}</p>
                                            </div>
                                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.development_plan.labels.location') }}</x-ui.field-label>
                                                <p class="mt-2 text-sm font-semibold text-zinc-900">{{ $session['location'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-3 rounded-2xl border border-dashed border-zinc-300 bg-white px-4 py-6 text-sm text-zinc-500">
                                {{ __('personnel::my_hr.development_plan.messages.no_sessions') }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-[28px] border border-dashed border-zinc-300 bg-white px-6 py-12 text-center shadow-sm">
                    <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.development_plan.empty.title') }}</h3>
                    <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.development_plan.empty.body') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
