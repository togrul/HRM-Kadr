@php
    $normalizeDuplicateText = static function (?string $text): string {
        $text = (string) $text;

        return trim((string) preg_replace('/(?:\s*(?:\(surət\)|\(copy\)|\(Surət\)|\(Copy\)))+/iu', '', $text));
    };

    $approvalBadgeClasses = static function (string $status): string {
        return match ($status) {
            'pending' => 'border-amber-200 bg-amber-50 text-amber-700',
            'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'rejected' => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-zinc-200 bg-zinc-50 text-zinc-600',
        };
    };
@endphp

<x-surface-card :title="__('notifications::common.tabs.history')" icon="icons.book-icon">
    <div class="space-y-4">
        <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_14rem]">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('notifications::common.helpers.search_history') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                <select wire:model.live="categoryFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                    <option value="all">{{ __('notifications::common.helpers.all_categories') }}</option>
                    @foreach ($categoryLabels as $category => $label)
                        <option value="{{ $category }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($campaigns as $campaign)
                <div class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                    <div class="space-y-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h4 class="text-xl font-semibold tracking-tight text-zinc-950">{{ $campaign->display_title ?? $campaign->title }}</h4>
                                    @if (($campaign->display_copy_count ?? 0) > 0)
                                        <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold text-sky-700">
                                            {{ __('notifications::common.badges.copy_label') }}{{ $campaign->display_copy_count > 1 ? ' ×'.$campaign->display_copy_count : '' }}
                                        </span>
                                    @endif
                                    <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ $categoryLabels[$campaign->category] ?? $campaign->category }}</span>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-semibold {{ $campaign->status === 'failed' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : ($campaign->status === 'sent' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-zinc-100 text-zinc-700 ring-1 ring-zinc-200') }}">{{ __('notifications::common.statuses.'.$campaign->status) }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-500">
                                    <span>{{ __('notifications::common.fields.creator') }}: <span class="font-medium text-zinc-700">{{ $campaign->creator?->name ?? '—' }}</span></span>
                                    <span>{{ __('notifications::common.fields.scheduled_at') }}: <span class="font-medium text-zinc-700">{{ optional($campaign->scheduled_at ?? $campaign->created_at)->format('d.m.Y H:i') ?: '—' }}</span></span>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold tracking-tight {{ $approvalBadgeClasses($campaign->approval_status) }}">
                                        {{ __('notifications::common.statuses.'.$campaign->approval_status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[25rem] xl:max-w-[28rem]">
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.stats.sent') }}</p>
                                    <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ $campaign->display_sent_count }}</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.stats.failed') }}</p>
                                    <p class="mt-2 text-2xl font-semibold text-rose-700">{{ $campaign->display_failed_count }}</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.labels.count') }}</p>
                                    <p class="mt-2 text-2xl font-semibold text-zinc-900">{{ $campaign->display_total_count }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 xl:grid-cols-2">
                            <section class="rounded-[1.45rem] border border-zinc-200 bg-zinc-50/50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.titles.audit_timeline') }}</p>
                                    <span class="rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-zinc-500">{{ $campaign->approvals->count() }}</span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @forelse ($campaign->approvals as $event)
                                        <div class="rounded-[1.35rem] border border-zinc-200 bg-white px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.03)]">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0 space-y-1">
                                                    <p class="text-sm font-semibold text-zinc-900">{{ __('notifications::common.audit.'.$event->action, ['action' => $event->action]) }}</p>
                                                    @if ($event->note)
                                                        <p class="text-sm leading-6 text-zinc-500">{{ $normalizeDuplicateText($event->note) }}</p>
                                                    @endif
                                                </div>
                                                <div class="shrink-0 text-right text-xs leading-5 text-zinc-500">
                                                    <p class="font-medium text-zinc-700">{{ $event->actor?->name ?? '—' }}</p>
                                                    <p>{{ optional($event->acted_at)->format('d.m.Y H:i') ?: '—' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-zinc-200 bg-white px-4 py-6 text-sm text-zinc-500">
                                            {{ __('notifications::common.helpers.history_empty') }}
                                        </div>
                                    @endforelse
                                </div>
                            </section>

                            <section class="rounded-[1.45rem] border border-zinc-200 bg-zinc-50/50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.titles.delivery_summary') }}</p>
                                    <span class="rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-zinc-500">
                                        {{ $campaign->dispatches->isNotEmpty() ? $campaign->dispatches->count() : __('notifications::common.statuses.'.$campaign->status) }}
                                    </span>
                                </div>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-white px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.03)]">
                                        <p class="text-[12px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('notifications::common.fields.status') }}</p>
                                        <p class="mt-2 text-sm uppercase tracking-tight font-semibold text-zinc-900">{{ __('notifications::common.statuses.'.$campaign->status) }}</p>
                                    </div>
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-white px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.03)]">
                                        <p class="text-[12px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('notifications::common.fields.approver') }}</p>
                                        <p class="mt-2 text-sm uppercase tracking-tight font-semibold text-zinc-900">{{ $campaign->approver?->name ?? '—' }}</p>
                                    </div>
                                </div>

                                @if ($campaign->dispatches->isNotEmpty())
                                    <div class="mt-4 space-y-3">
                                        @foreach ($campaign->dispatches as $dispatch)
                                            <div class="rounded-[1.35rem] border border-zinc-200 bg-white px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.03)]">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="min-w-0 space-y-1">
                                                        <p class="text-sm font-semibold text-zinc-900">{{ $dispatch->user?->name ?? __('notifications::common.helpers.dispatch_deleted_user') }}</p>
                                                        <p class="break-all text-sm text-zinc-500">{{ data_get($dispatch->meta, 'recipient_email') ?: $dispatch->user?->email ?: '—' }}</p>
                                                        @if ($dispatch->error_message)
                                                            <p class="text-sm leading-6 text-rose-600">{{ $dispatch->error_message }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="shrink-0 text-right">
                                                        <p class="text-sm font-semibold {{ $dispatch->status === 'failed' ? 'text-rose-700' : 'text-emerald-700' }}">{{ __('notifications::common.statuses.'.$dispatch->status) }}</p>
                                                        <p class="mt-1 text-xs text-zinc-500">{{ __('notifications::common.labels.attempts') }}: {{ max(1, (int) $dispatch->attempt_count) }}</p>
                                                        <p class="mt-1 text-xs text-zinc-400">{{ optional($dispatch->sent_at ?? $dispatch->failed_at ?? $dispatch->last_attempt_at)->format('d.m.Y H:i') ?: '—' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-4 space-y-3">
                                        <div class="rounded-[1.35rem] border border-zinc-200 bg-white px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.03)]">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.titles.failure_reason') }}</p>
                                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                                @if ($campaign->status === 'failed')
                                                    {{ $normalizeDuplicateText($campaign->latest_failed_event?->note) ?: __('notifications::common.helpers.dispatch_history_failed_without_recipient') }}
                                                @else
                                                    {{ __('notifications::common.helpers.dispatch_history_waiting') }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="rounded-[1.35rem] border border-zinc-200 bg-white px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.03)]">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.titles.dispatch_history') }}</p>
                                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                                @if ($campaign->status === 'failed')
                                                    {{ __('notifications::common.helpers.no_recipients') }}
                                                @else
                                                    {{ __('notifications::common.helpers.dispatch_history_none') }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </section>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[1.7rem] border border-dashed border-zinc-200 bg-zinc-50/70 px-5 py-10 text-center">
                    <p class="text-sm font-semibold text-zinc-900">{{ __('notifications::common.tabs.history') }}</p>
                    <p class="mt-2 text-sm leading-6 text-zinc-500">{{ __('notifications::common.helpers.history_empty') }}</p>
                </div>
            @endforelse
        </div>

        <div>
            {{ $campaigns->links() }}
        </div>
    </div>
</x-surface-card>
