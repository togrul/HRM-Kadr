@php
    $approvalBadgeClasses = static function (string $status): string {
        return match ($status) {
            'pending' => 'amber',
            'approved' => 'emerald',
            'rejected' => 'rose',
            default => 'muted',
        };
    };

    $statusChipMode = static function (string $status): string {
        return match ($status) {
            'sent' => 'emerald',
            'failed' => 'rose',
            'queued', 'pending' => 'amber',
            'approved' => 'emerald',
            'rejected', 'cancelled' => 'rose',
            default => 'muted',
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
                    <x-ui.input-shell>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('notifications::common.helpers.search_campaigns') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                    </x-ui.input-shell>
                    <x-ui.input-shell>
                        <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                            <option value="all">{{ __('notifications::common.helpers.all_statuses') }}</option>
                            <option value="draft">{{ __('notifications::common.statuses.draft') }}</option>
                            <option value="queued">{{ __('notifications::common.statuses.queued') }}</option>
                            <option value="sent">{{ __('notifications::common.statuses.sent') }}</option>
                            <option value="failed">{{ __('notifications::common.statuses.failed') }}</option>
                            <option value="cancelled">{{ __('notifications::common.statuses.cancelled') }}</option>
                        </select>
                    </x-ui.input-shell>
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
                                    <x-notification.chip mode="sky" size="sm" uppercase>
                                        {{ __('notifications::common.badges.copy_label') }}{{ $campaign->display_copy_count > 1 ? ' ×'.$campaign->display_copy_count : '' }}
                                    </x-notification.chip>
                                @endif
                                <x-notification.chip mode="muted" size="sm" uppercase>{{ $categoryLabels[$campaign->category] ?? $campaign->category }}</x-notification.chip>
                                <x-notification.chip :mode="$statusChipMode($campaign->status)" size="sm" uppercase>{{ __('notifications::common.statuses.'.$campaign->status) }}</x-notification.chip>
                                @if ($campaign->scheduled_at)
                                    <x-notification.chip mode="amber" size="sm">
                                        {{ __('notifications::common.fields.scheduled_at') }}: {{ $campaign->scheduled_at->format('d.m.Y H:i') }}
                                    </x-notification.chip>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-notification.chip mode="muted">{{ $campaign->display_sent_count }} {{ __('notifications::common.stats.sent') }}</x-notification.chip>
                                <x-notification.chip mode="muted">{{ $campaign->display_failed_count }} {{ __('notifications::common.stats.failed') }}</x-notification.chip>
                                <x-notification.chip mode="muted">{{ $campaign->display_total_count }} {{ __('notifications::common.helpers.queued_dispatches') }}</x-notification.chip>
                                <x-notification.chip :mode="$approvalBadgeClasses($campaign->approval_status)" uppercase>
                                    {{ __('notifications::common.statuses.'.$campaign->approval_status) }}
                                </x-notification.chip>
                            </div>
                            <div class="flex flex-wrap gap-4 text-xs text-zinc-500">
                                <span>{{ __('notifications::common.fields.creator') }}: {{ $campaign->creator?->name ?? '—' }}</span>
                                <span>{{ $campaign->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>

                        @if ($canManageCampaigns)
                            <div class="flex flex-wrap items-center justify-end gap-2 xl:max-w-[18rem]">
                                <x-ui.async-button variant="secondary" size="sm" wire:click="duplicate({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="duplicate">{{ __('notifications::common.buttons.duplicate') }}</x-ui.async-button>
                                <x-ui.async-button variant="info" size="sm" wire:click="resend({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="resend">{{ __('notifications::common.buttons.resend') }}</x-ui.async-button>
                                @if ($campaign->failed_dispatches_count > 0)
                                    <x-ui.async-button variant="success" size="sm" wire:click="retry({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="retry">{{ __('notifications::common.buttons.retry') }}</x-ui.async-button>
                                @endif
                                @if ($campaign->status !== 'sent')
                                    <x-ui.async-button variant="secondary" size="sm" wire:click="dispatchNow({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="dispatchNow">{{ __('notifications::common.buttons.send_now') }}</x-ui.async-button>
                                @endif
                                <x-ui.async-button variant="secondary" size="sm" wire:click="updateStatus({{ $campaign->id }}, 'queued')" wire:loading.attr="disabled" wire:target="updateStatus">{{ __('notifications::common.buttons.queue') }}</x-ui.async-button>
                                <x-ui.async-button variant="warning" size="sm" wire:click="updateStatus({{ $campaign->id }}, 'cancelled')" wire:loading.attr="disabled" wire:target="updateStatus">{{ __('notifications::common.buttons.cancel') }}</x-ui.async-button>
                                <x-ui.async-button variant="danger" size="sm" wire:click="delete({{ $campaign->id }})" wire:loading.attr="disabled" wire:target="delete">{{ __('notifications::common.buttons.delete') }}</x-ui.async-button>
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
                                            <p class="text-xs uppercase tracking-tight font-semibold text-zinc-400">{{ __('notifications::common.channels.'.$dispatch->channel) }}</p>
                                            @if ($dispatch->error_message)
                                                <p class="text-xs leading-5 text-rose-600">{{ $dispatch->error_message }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <x-notification.chip :mode="$statusChipMode($dispatch->status)" size="sm" uppercase>{{ __('notifications::common.statuses.'.$dispatch->status) }}</x-notification.chip>
                                            <p class="text-xs text-zinc-400">{{ optional($dispatch->sent_at ?? $dispatch->failed_at ?? $dispatch->last_attempt_at)->format('d.m.Y H:i') ?: '—' }}</p>
                                            <p class="text-xs text-zinc-500">{{ __('notifications::common.labels.attempts') }}: {{ max(1, (int) $dispatch->attempt_count) }}</p>
                                            @if (data_get($dispatch->meta, 'next_retry_at'))
                                                <x-notification.chip mode="amber" size="sm">{{ __('notifications::common.labels.next_retry_at') }}: {{ \Illuminate\Support\Carbon::parse(data_get($dispatch->meta, 'next_retry_at'))->format('d.m.Y H:i') }}</x-notification.chip>
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
