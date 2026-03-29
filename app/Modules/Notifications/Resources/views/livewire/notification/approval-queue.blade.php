@php
    $normalizeCampaignTitle = static function (string $title): string {
        return trim((string) preg_replace('/(?:\s*(?:\(surət\)|\(copy\)|\(Surət\)|\(Copy\)))+/iu', '', $title));
    };
@endphp

<x-surface-card :title="__('notifications::common.titles.approval_queue')" icon="icons.pending-icon">
    <div class="space-y-3">
        @forelse ($campaigns as $campaign)
            <div class="rounded-[1.6rem] border border-zinc-200 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.92))] px-4 py-4 shadow-[0_14px_32px_rgba(15,23,42,0.04)] sm:px-5">
                <div class="space-y-4">
                    <div class="space-y-3">
                        <p class="max-w-[30ch] text-[clamp(1.15rem,1.45vw,1.45rem)] font-semibold leading-[1.15] tracking-tight text-zinc-950 break-words">
                            {{ $normalizeCampaignTitle($campaign->title) }}
                        </p>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-100 px-3 py-1 text-[12px] font-semibold uppercase tracking-tight text-zinc-500">
                                {{ $categoryLabels[$campaign->category] ?? $campaign->category }}
                            </span>
                            <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1 text-[12px] font-semibold uppercase tracking-tight text-zinc-500">
                                {{ __('notifications::common.channels.'.$campaign->channel) }}
                            </span>
                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[12px] uppercase font-semibold text-amber-700">
                                {{ __('notifications::common.statuses.pending') }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm leading-6 text-zinc-500">
                            @if ($campaign->scheduled_at)
                                <span>{{ __('notifications::common.fields.scheduled_at') }}: {{ $campaign->scheduled_at->format('d.m.Y H:i') }}</span>
                            @endif
                            <span>{{ __('notifications::common.fields.creator') }}: {{ $campaign->created_at?->format('d.m.Y H:i') }}</span>
                        </div>
                    </div>

                    @if ($canApproveCampaigns)
                        <div class="grid gap-2 sm:grid-cols-2 xl:max-w-[26rem]">
                            <button
                                type="button"
                                wire:click="approve({{ $campaign->id }})"
                                wire:loading.attr="disabled"
                                wire:target="approve"
                                class="inline-flex h-11 items-center justify-center rounded-2xl bg-zinc-950 px-4 text-sm font-semibold text-white shadow-[0_12px_28px_rgba(15,23,42,0.12)] disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {{ __('notifications::common.buttons.approve') }}
                            </button>
                            <button
                                type="button"
                                wire:click="reject({{ $campaign->id }})"
                                wire:loading.attr="disabled"
                                wire:target="reject"
                                class="inline-flex py-2 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {{ __('notifications::common.buttons.reject') }}
                            </button>
                        </div>
                    @endif
                </div>

                <div class="mt-4 rounded-[1.35rem] border border-zinc-200/80 bg-white/80 p-3.5">
                    <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.note') }}</label>
                    <textarea
                        wire:model.defer="notes.{{ $campaign->id }}"
                        rows="3"
                        class="mt-2 w-full rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3 text-sm leading-6 text-zinc-800"
                    ></textarea>
                </div>
            </div>
        @empty
            <div class="rounded-[1.7rem] border border-dashed border-zinc-200 bg-[linear-gradient(180deg,rgba(250,250,250,0.85),rgba(244,244,245,0.75))] px-5 py-10 text-center">
                <p class="text-base font-semibold text-zinc-900">{{ __('notifications::common.titles.approval_queue') }}</p>
                <p class="mt-2 text-sm leading-6 text-zinc-500">{{ __('notifications::common.helpers.approval_queue_empty') }}</p>
            </div>
        @endforelse
    </div>
</x-surface-card>
