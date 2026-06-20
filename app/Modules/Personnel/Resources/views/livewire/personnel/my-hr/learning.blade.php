@php
    $payload = $this->payload;
    $summary = $payload['summary'];
    $rows = $payload['rows'];
    $statCards = [
        ['label' => __('personnel::my_hr.learning.summary.total'), 'value' => $summary['total']],
        ['label' => __('personnel::my_hr.learning.summary.pending'), 'value' => $summary['pending']],
        ['label' => __('personnel::my_hr.learning.summary.completed'), 'value' => $summary['completed']],
        ['label' => __('personnel::my_hr.learning.summary.required'), 'value' => $summary['required']],
    ];
@endphp

<div class="space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.kicker') }}</x-ui.field-label>
            <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.learning.title') }}</h2>
            <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.learning.description') }}</p>
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
        <div class="space-y-4">
            @forelse ($rows as $row)
                <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1 space-y-3">
                            <div class="inline-flex max-w-full rounded-[24px] border border-zinc-200 bg-zinc-50 px-5 py-3">
                                <h3 class="max-w-[38rem] text-lg font-semibold tracking-tight text-zinc-950">{{ $row['title'] }}</h3>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-notification.chip mode="{{ $row['status_mode'] }}">{{ $row['status_label'] }}</x-notification.chip>
                                <x-notification.chip mode="sky">{{ $row['content_type_label'] }}</x-notification.chip>
                                @if ($row['estimated_minutes'])
                                    <x-notification.chip mode="muted">{{ __('personnel::my_hr.learning.labels.duration') }}: {{ $row['estimated_minutes'] }} {{ __('personnel::my_hr.learning.labels.minutes') }}</x-notification.chip>
                                @endif
                                @if ($row['is_required'])
                                    <x-notification.chip mode="amber">{{ __('personnel::my_hr.learning.labels.required') }}</x-notification.chip>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <x-ui.async-button type="button" variant="secondary" wire:click="openContent({{ $row['id'] }})" wire:target="openContent({{ $row['id'] }})">
                                {{ __('personnel::my_hr.learning.actions.open_content') }}
                            </x-ui.async-button>
                            @if ($row['can_complete'])
                                <x-ui.async-button type="button" variant="primary" wire:click="complete({{ $row['id'] }})" wire:target="complete({{ $row['id'] }})">
                                    {{ __('personnel::my_hr.learning.actions.mark_completed') }}
                                </x-ui.async-button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.assigned_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['assigned_at'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.due_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['due_at'] ?: '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.opened_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['opened_at'] ?: '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.completed_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['completed_at'] ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-[24px] border border-zinc-200 bg-zinc-50/70 px-5 py-4">
                        <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.description') }}</x-ui.field-label>
                        <p class="mt-2 text-sm leading-7 text-zinc-700">{{ $row['description'] }}</p>
                    </div>
                </div>
            @empty
                <div class="rounded-[28px] bg-[#f5f5f7] px-6 py-12 text-center shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_10px_22px_rgba(0,0,0,0.035)]">
                    <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.learning.empty.title') }}</h3>
                    <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.learning.empty.body') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
