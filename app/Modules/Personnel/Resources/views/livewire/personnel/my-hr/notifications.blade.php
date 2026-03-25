@php
    $statCards = [
        ['label' => __('personnel::my_hr.notifications.summary.total'), 'value' => $summary['total']],
        ['label' => __('personnel::my_hr.notifications.summary.today'), 'value' => $summary['today']],
        ['label' => __('personnel::my_hr.notifications.summary.this_week'), 'value' => $summary['this_week']],
        ['label' => __('personnel::my_hr.notifications.summary.older'), 'value' => $summary['older']],
    ];
@endphp

<div class="space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.notifications.kicker') }}</x-ui.field-label>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.notifications.title') }}</h2>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.notifications.description') }}</p>
            </div>

            @if ($summary['total'] > 0)
                <button type="button" wire:click="clearNotifications" wire:loading.attr="disabled" class="inline-flex items-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                    {{ __('personnel::my_hr.notifications.actions.clear_all') }}
                </button>
            @endif
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
            @forelse ($groupedNotifications as $group)
                <div class="rounded-[1.4rem] border border-zinc-200 bg-white shadow-sm">
                    <div class="border-b border-zinc-200 px-6 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $group['label'] }}</p>
                    </div>
                    <div class="space-y-3 px-4 py-4">
                        @foreach ($group['items'] as $notification)
                            <x-notification.list-item :$notification />
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-[28px] border border-dashed border-zinc-300 bg-white px-6 py-12 text-center shadow-sm">
                    <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.notifications.empty.title') }}</h3>
                    <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.notifications.empty.body') }}</p>
                </div>
            @endforelse
        </div>

        @if (method_exists($notifications, 'links'))
            <div class="mt-5">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
