@php
    $approvalBadgeClasses = static function (string $status): string {
        return match ($status) {
            'pending' => 'border-amber-200 bg-amber-50 text-amber-700',
            'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'rejected' => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-zinc-200 bg-zinc-50 text-zinc-600',
        };
    };

    $eventActorName = static function ($campaign, $event): string {
        return match ($event->action) {
            'approved', 'rejected' => $campaign->approver?->name ?? '—',
            default => $campaign->creator?->name ?? '—',
        };
    };
@endphp
<x-surface-card :title="__('notifications::common.tabs.campaigns')" icon="icons.clock-icon">
    <div class="space-y-4">
        <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.tabs.campaigns') }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('notifications::common.helpers.campaign_board_hint') }}</p>
                </div>
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_14rem] lg:min-w-[32rem]">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('notifications::common.helpers.search_campaigns') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
            <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                <option value="all">{{ __('notifications::common.helpers.all_statuses') }}</option>
                <option value="draft">{{ __('notifications::common.statuses.draft') }}</option>
                <option value="queued">{{ __('notifications::common.statuses.queued') }}</option>
                <option value="sent">{{ __('notifications::common.statuses.sent') }}</option>
                <option value="failed">{{ __('notifications::common.statuses.failed') }}</option>
                <option value="cancelled">{{ __('notifications::common.statuses.cancelled') }}</option>
            </select>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            @forelse ($campaigns as $campaign)
                <div class="rounded-[1.6rem] border border-zinc-200 bg-white px-4 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto]">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-base font-semibold tracking-tight text-zinc-950">{{ $campaign->display_title ?? $campaign->title }}</h4>
                                @if (($campaign->display_copy_count ?? 0) > 0)
                                    <span class="whitespace-nowrap rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                                        {{ __('notifications::common.badges.copy_label') }}{{ $campaign->display_copy_count > 1 ? ' ×'.$campaign->display_copy_count : '' }}
                                    </span>
                                @endif
                                <span class="whitespace-nowrap rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ $categoryLabels[$campaign->category] ?? $campaign->category }}</span>
                                <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $campaign->status === 'sent' ? 'bg-emerald-50 text-emerald-700' : ($campaign->status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-zinc-100 text-zinc-600') }}">{{ __('notifications::common.statuses.'.$campaign->status) }}</span>
                                @if ($campaign->scheduled_at)
                                    <span class="whitespace-nowrap rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                        {{ __('notifications::common.fields.scheduled_at') }}: {{ $campaign->scheduled_at->format('d.m.Y H:i') }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ $campaign->display_sent_count }} {{ __('notifications::common.stats.sent') }}</span>
                                <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ $campaign->display_failed_count }} {{ __('notifications::common.stats.failed') }}</span>
                                <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ $campaign->display_total_count }} {{ __('notifications::common.helpers.queued_dispatches') }}</span>
                                <span class="inline-flex items-center whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-semibold tracking-tight {{ $approvalBadgeClasses($campaign->approval_status) }}">
                                    {{ __('notifications::common.statuses.'.$campaign->approval_status) }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-4 text-xs text-zinc-500">
                                <span>{{ __('notifications::common.fields.creator') }}: {{ $campaign->creator?->name ?? '—' }}</span>
                                <span>{{ $campaign->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>

                        @if ($canManageCampaigns)
                            <div class="flex flex-wrap items-center justify-end gap-2 xl:max-w-[18rem]">
                                <button type="button" wire:click="duplicate({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="duplicate" class="rounded-xl border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-600 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.duplicate') }}</button>
                                <button type="button" wire:click="resend({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="resend" class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.resend') }}</button>
                                @if ($campaign->failed_dispatches_count > 0)
                                    <button type="button" wire:click="retry({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="retry" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.retry') }}</button>
                                @endif
                                @if ($campaign->status !== 'sent')
                                    <button type="button" wire:click="dispatchNow({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="dispatchNow" class="rounded-xl border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.send_now') }}</button>
                                @endif
                                <button type="button" wire:click="updateStatus({{ $campaign->id }}, 'queued')" wire:loading.attr="disabled" wire:target="updateStatus" class="rounded-xl border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-600 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.queue') }}</button>
                                <button type="button" wire:click="updateStatus({{ $campaign->id }}, 'cancelled')" wire:loading.attr="disabled" wire:target="updateStatus" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.cancel') }}</button>
                                <button type="button" wire:click="delete({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="delete" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.delete') }}</button>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(17rem,0.88fr)]">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <p class="text-sm font-semibold text-zinc-900">{{ __('notifications::common.titles.dispatch_history') }}</p>
                            </div>
                            @forelse ($campaign->dispatches as $dispatch)
                                <div class="border-b border-zinc-200 px-4 py-3 last:border-b-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="space-y-1">
                                            <p class="text-sm font-medium text-zinc-900">{{ data_get($dispatch->meta, 'recipient_email') ?: __('notifications::common.helpers.dispatch_deleted_user') }}</p>
                                            <p class="text-xs uppercase tracking-[0.14em] text-zinc-400">{{ __('notifications::common.channels.'.$dispatch->channel) }}</p>
                                            @if ($dispatch->error_message)
                                                <p class="text-xs leading-5 text-rose-600">{{ $dispatch->error_message }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold {{ $dispatch->status === 'failed' ? 'text-rose-600' : 'text-zinc-700' }}">{{ __('notifications::common.statuses.'.$dispatch->status) }}</p>
                                            <p class="text-xs text-zinc-400">{{ optional($dispatch->sent_at ?? $dispatch->failed_at ?? $dispatch->last_attempt_at)->format('d.m.Y H:i') ?: '—' }}</p>
                                            <p class="text-xs text-zinc-500">{{ __('notifications::common.labels.attempts') }}: {{ max(1, (int) $dispatch->attempt_count) }}</p>
                                            @if (data_get($dispatch->meta, 'next_retry_at'))
                                                <p class="text-xs text-amber-600">{{ __('notifications::common.labels.next_retry_at') }}: {{ \Illuminate\Support\Carbon::parse(data_get($dispatch->meta, 'next_retry_at'))->format('d.m.Y H:i') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-6 text-sm text-zinc-500">
                                    {{ __('notifications::common.helpers.campaign_none') }}
                                </div>
                            @endforelse
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <p class="text-sm font-semibold text-zinc-900">{{ __('notifications::common.titles.audit_timeline') }}</p>
                            </div>
                            <div class="space-y-2 p-4">
                                @forelse ($campaign->approvals as $event)
                                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-zinc-900">{{ __('notifications::common.audit.'.$event->action, ['action' => $event->action]) }}</p>
                                                @if ($event->note)
                                                    <p class="mt-1 text-xs leading-5 text-zinc-500">{{ $event->note }}</p>
                                                @endif
                                            </div>
                                            <div class="text-right text-xs text-zinc-500">
                                                <p>{{ $eventActorName($campaign, $event) }}</p>
                                                <p>{{ optional($event->acted_at)->format('d.m.Y H:i') ?: '—' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-zinc-500">{{ __('notifications::common.helpers.history_empty') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[1.7rem] border border-dashed border-zinc-200 bg-zinc-50/70 px-5 py-10 text-center">
                    <p class="text-sm font-semibold text-zinc-900">{{ __('notifications::common.tabs.campaigns') }}</p>
                    <p class="mt-2 text-sm leading-6 text-zinc-500">{{ __('notifications::common.helpers.campaign_none') }}</p>
                </div>
            @endforelse
        </div>

        <div>
            {{ $campaigns->links() }}
        </div>
    </div>
</x-surface-card>
