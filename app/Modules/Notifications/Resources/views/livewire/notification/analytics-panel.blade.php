<x-surface-card :title="__('notifications::common.tabs.analytics')" icon="icons.report-chart-icon">
    <div class="space-y-4">
        <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4">
            <div class="grid gap-4 xl:grid-cols-[16rem_minmax(0,1fr)]">
                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.date_range') }}</label>
                    <select wire:model.live="range" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @foreach ($rangeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($range === 'custom')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.date_from') }}</label>
                            <input type="date" wire:model.live="dateFrom" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.date_to') }}</label>
                            <input type="date" wire:model.live="dateTo" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        </div>
                    </div>
                @else
                    <div class="flex items-end">
                        <p class="text-sm text-zinc-500">{{ __('notifications::common.helpers.analytics_range_hint') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-4">
            <div class="rounded-[1.6rem] border border-zinc-200 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.stats.sent') }}</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $stats['sent'] }}</p>
            </div>
            <div class="rounded-[1.6rem] border border-zinc-200 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.stats.failed') }}</p>
                <p class="mt-3 text-3xl font-semibold text-rose-700">{{ $stats['failed'] }}</p>
            </div>
            <div class="rounded-[1.6rem] border border-zinc-200 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.stats.approval_turnaround') }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $stats['approval_turnaround_minutes'] !== null ? $stats['approval_turnaround_minutes'].' dəq' : '—' }}</p>
            </div>
            <div class="rounded-[1.6rem] border border-zinc-200 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.stats.scheduled') }}</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ $stats['scheduled'] }}</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-[1.6rem] border border-zinc-200 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.titles.channel_health') }}</p>
                <div class="mt-4 space-y-3">
                    @forelse ($statusByChannel as $channelRow)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                            <div class="grid gap-4 min-[980px]:grid-cols-[minmax(0,12rem)_minmax(0,1fr)]">
                                <div class="rounded-[1.35rem] border border-zinc-200 bg-white px-4 py-4">
                                    <p class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('notifications::common.channels.'.$channelRow['channel']) }}</p>
                                    <p class="mt-2 text-sm leading-6 text-zinc-500">{{ __('notifications::common.helpers.channel_health_hint') }}</p>
                                </div>
                                <div class="grid gap-3 md:grid-cols-3">
                                    <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('notifications::common.stats.sent') }}</p>
                                        <p class="mt-2 text-2xl font-semibold tracking-tight text-emerald-700">{{ $channelRow['sent'] }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('notifications::common.stats.failed') }}</p>
                                        <p class="mt-2 text-2xl font-semibold tracking-tight text-rose-700">{{ $channelRow['failed'] }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('notifications::common.statuses.pending') }}</p>
                                        <p class="mt-2 text-2xl font-semibold tracking-tight text-amber-700">{{ $channelRow['pending'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-8 text-sm text-zinc-500">
                            {{ __('notifications::common.helpers.channel_health_none') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="grid gap-4 min-[1500px]:grid-cols-2">
                <div class="rounded-[1.6rem] border border-zinc-200 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                    <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.titles.failure_reasons') }}</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($failureReasons as $failure)
                            <div class="rounded-2xl border border-rose-200 bg-rose-50/60 px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-rose-900">{{ $failure['reason'] }}</p>
                                        <p class="mt-1 text-xs text-rose-700">{{ $failure['latest_recipient'] ?: '—' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-rose-800">{{ $failure['count'] }}</p>
                                        <p class="text-xs text-rose-700">{{ $failure['latest_failed_at'] ?: '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-8 text-sm text-zinc-500">
                                {{ __('notifications::common.helpers.failure_preview_none') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[1.6rem] border border-zinc-200 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                    <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.titles.provider_delivery') }}</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($providerStats as $provider)
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                <div class="grid gap-3 min-[980px]:grid-cols-[minmax(0,1fr)_auto]">
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-900">{{ $provider['driver'] }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ __('notifications::common.labels.attempts') }}: {{ $provider['attempts'] }}</p>
                                        @if ($provider['latest_provider_message_id'])
                                            <p class="mt-1 break-all text-[11px] leading-5 text-zinc-500">{{ $provider['latest_provider_message_id'] }}</p>
                                        @endif
                                    </div>
                                    <div class="grid gap-2 min-[980px]:w-[13rem] sm:grid-cols-2 min-[980px]:sm:grid-cols-2">
                                        <div class="rounded-xl border border-zinc-200 bg-white px-3 py-2">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('notifications::common.stats.sent') }}</p>
                                            <p class="mt-1 text-lg font-semibold text-emerald-700">{{ $provider['sent'] }}</p>
                                        </div>
                                        <div class="rounded-xl border border-zinc-200 bg-white px-3 py-2">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('notifications::common.stats.failed') }}</p>
                                            <p class="mt-1 text-lg font-semibold text-rose-700">{{ $provider['failed'] }}</p>
                                        </div>
                                    </div>
                                </div>
                                @if ($provider['latest_error'])
                                    <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50/70 px-3 py-2 text-xs leading-5 text-rose-700">{{ $provider['latest_error'] }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-8 text-sm text-zinc-500">
                                {{ __('notifications::common.helpers.provider_preview_none') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-surface-card>
