@php
    $metrics = [
        ['label' => __('personnel::my_hr.notifications.summary.total'), 'value' => $summary['total'], 'dot' => 'bg-[#a1a1aa]'],
        ['label' => __('personnel::my_hr.notifications.summary.today'), 'value' => $summary['today'], 'dot' => 'bg-[#0284c7]'],
        ['label' => __('personnel::my_hr.notifications.summary.this_week'), 'value' => $summary['this_week'], 'dot' => 'bg-[#059669]'],
        ['label' => __('personnel::my_hr.notifications.summary.older'), 'value' => $summary['older'], 'dot' => 'bg-[#d4d4d8]'],
    ];
@endphp

<div class="flex flex-col gap-4">
    <section class="rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="hrm-eyebrow">{{ __('personnel::my_hr.notifications.kicker') }}</p>
                <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-ink-muted">{{ __('personnel::my_hr.notifications.description') }}</p>
            </div>

            @if ($summary['total'] > 0)
                <x-pill-button variant="danger" wire:click="clearNotifications" wire:loading.attr="disabled" wire:target="clearNotifications">
                    {{ __('personnel::my_hr.notifications.actions.clear_all') }}
                </x-pill-button>
            @endif
        </div>

        @include('personnel::livewire.personnel.my-hr.partials.metric-strip', ['metrics' => $metrics])
    </section>

    @forelse ($groupedNotifications as $group)
        <section class="rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-2.5">
                <p class="hrm-eyebrow">{{ $group['label'] }}</p>
            </div>
            <div class="space-y-3 p-3">
                @foreach ($group['items'] as $notification)
                    <x-notification.list-item :$notification />
                @endforeach
            </div>
        </section>
    @empty
        <x-ui.empty-state icon="icons.info-circle-icon" :title="__('personnel::my_hr.notifications.empty.title')" :message="__('personnel::my_hr.notifications.empty.body')" />
    @endforelse

    @if (method_exists($notifications, 'links'))
        {{ $notifications->links() }}
    @endif
</div>
