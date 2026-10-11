<div class="contents">
    @if ($this->upcomingPlans->isNotEmpty())
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('benefits::dashboard.renewals.upcoming') }}</h2>
                <p class="mt-0.5 text-[12px] text-ink-faint">{{ __('benefits::dashboard.renewals.upcoming_help') }}</p>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @foreach ($this->upcomingPlans as $plan)
                    <div wire:key="benefits-upcoming-{{ $plan->id }}" class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $plan->name }}</p>
                            <p class="text-[11.5px] text-ink-faint">{{ __('benefits::dashboard.renewals.contract_ends', ['date' => $plan->contract_end?->format('d.m.Y')]) }}</p>
                        </div>
                        @if ($canManage)
                            <x-pill-button wire:click="openWindow({{ $plan->id }})" wire:loading.attr="disabled" wire:target="openWindow">
                                {{ __('benefits::dashboard.actions.open_window') }}
                            </x-pill-button>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('benefits::dashboard.renewals.title') }}</h2>
            <p class="mt-0.5 text-[12px] text-ink-faint">{{ __('benefits::dashboard.renewals.help') }}</p>
        </div>

        <x-table.tbl :headers="[
            __('benefits::dashboard.columns.plan'),
            __('benefits::dashboard.columns.window'),
            __('benefits::dashboard.columns.decisions'),
            __('benefits::dashboard.columns.status'),
            __('benefits::dashboard.columns.actions'),
        ]">
            @forelse ($this->windows as $window)
                @php $counts = (array) $window->getAttribute('decision_counts'); @endphp
                <tr wire:key="benefits-window-{{ $window->id }}">
                    <x-table.td standart-width>
                        <p class="max-w-[240px] truncate text-[13px] font-medium text-ink">{{ $window->plan?->name }}</p>
                        <p class="text-[11px] text-ink-faint">{{ $window->plan?->auto_renew ? __('benefits::dashboard.renewals.auto_renew') : __('benefits::dashboard.renewals.manual_renew') }}</p>
                    </x-table.td>
                    <x-table.td>
                        <p class="hrm-num text-[12.5px] text-ink-soft">{{ $window->opens_on?->format('d.m.Y') }} – {{ $window->closes_on?->format('d.m.Y') }}</p>
                        <p class="text-[11px] text-ink-faint">{{ __('benefits::dashboard.renewals.next_term', ['date' => $window->next_contract_end?->format('d.m.Y')]) }}</p>
                    </x-table.td>
                    <x-table.td>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-small-badge mode="green">{{ __('benefits::dashboard.decisions.renew') }}: {{ (int) ($counts['renew'] ?? 0) }}</x-small-badge>
                            <x-small-badge mode="rose">{{ __('benefits::dashboard.decisions.opt_out') }}: {{ (int) ($counts['opt_out'] ?? 0) }}</x-small-badge>
                            <x-small-badge mode="secondary">{{ __('benefits::dashboard.decisions.pending') }}: {{ (int) ($counts['pending'] ?? 0) }}</x-small-badge>
                        </div>
                    </x-table.td>
                    <x-table.td>
                        <x-small-badge :mode="$window->status === 'open' ? 'amber' : 'secondary'" dot>{{ __('benefits::dashboard.statuses.window_'.$window->status) }}</x-small-badge>
                    </x-table.td>
                    <x-table.td :isButton="true">
                        @if ($canManage && $window->status === 'open')
                            <div class="flex items-center justify-end">
                                <x-pill-button x-on:click="{{ $confirm('closeWindow('.$window->id.')', 'close_window', 'close_window') }}">
                                    {{ __('benefits::dashboard.actions.close_window') }}
                                </x-pill-button>
                            </div>
                        @endif
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10">
                        <x-ui.empty-state icon="icons.calendar-icon" :title="__('benefits::dashboard.renewals.empty')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>
    </section>
</div>
