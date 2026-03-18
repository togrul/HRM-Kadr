@php
    $displayTemplateKey = static function (string $key): string {
        return match ($key) {
            'birthday.default' => __('notifications::common.template_keys.birthday.default'),
            'position-change.default' => __('notifications::common.template_keys.position_change.default'),
            'holiday.default' => __('notifications::common.template_keys.holiday.default'),
            default => $key,
        };
    };
    $displayTrigger = static function (?string $trigger): string {
        return $trigger ? __('notifications::common.triggers.'.$trigger) : '—';
    };
    $normalizeCampaignTitle = static function (string $title): string {
        return trim((string) preg_replace('/(?:\s*(?:\(surət\)|\(copy\)|\(Surət\)|\(Copy\)))+/iu', '', $title));
    };
    $fallbackPreviewText = __('notifications::common.flows.not_created');
@endphp

<div class="space-y-5">
    <x-surface-card :title="__('notifications::common.titles.flow_starter')" icon="icons.cake-icon">
        <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
            @foreach ([
                'birthday' => __('notifications::common.flows.birthday_starter'),
                'position_change' => __('notifications::common.flows.position_change_starter'),
                'holiday' => __('notifications::common.flows.holiday_starter'),
            ] as $flowKey => $flowTitle)
                <div class="rounded-[1.75rem] border border-zinc-200 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.94))] p-4 shadow-[0_18px_36px_rgba(15,23,42,0.05)] sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 space-y-2">
                            <h3 class="text-[1.1rem] font-semibold leading-6 tracking-tight text-zinc-950">
                                {{ $flowTitle }}
                            </h3>
                        </div>
                        <x-ui.async-button
                            type="button"
                            variant="primary"
                            wire:click="{{ $flowKey === 'birthday' ? 'seedBirthdayStarter' : ($flowKey === 'position_change' ? 'seedPositionChangeStarter' : 'seedHolidayStarter') }}"
                            class="shrink-0 shadow-[0_14px_28px_rgba(15,23,42,0.16)]"
                        >
                            {{ __('notifications::common.buttons.seed') }}
                        </x-ui.async-button>
                    </div>

                    <p class="w-full text-sm leading-2 text-zinc-500 mt-2">
                      @if ($flowKey === 'birthday')
                          {{ __('notifications::common.flows.birthday_starter_hint') }}
                      @elseif ($flowKey === 'position_change')
                          {{ __('notifications::common.flows.position_change_starter_hint') }}
                      @else
                          {{ __('notifications::common.flows.holiday_starter_hint') }}
                      @endif
                  </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <x-notification.chip mode="neutral" size="sm" uppercase class="shadow-[0_6px_14px_rgba(15,23,42,0.04)]">{{ $displayTemplateKey($starterFlows[$flowKey]['template_key']) }}</x-notification.chip>
                        <x-notification.chip mode="neutral" size="sm" uppercase class="shadow-[0_6px_14px_rgba(15,23,42,0.04)]">{{ $displayTrigger($starterFlows[$flowKey]['trigger'] ?? null) }}</x-notification.chip>
                        <x-notification.chip mode="neutral" size="sm" uppercase class="shadow-[0_6px_14px_rgba(15,23,42,0.04)]">{{ __('notifications::common.channels.'.$starterFlows[$flowKey]['channel']) }}</x-notification.chip>
                        <x-notification.chip :mode="$starterFlows[$flowKey]['approval_required'] ? 'amber' : 'emerald'" size="sm" uppercase>{{ $starterFlows[$flowKey]['approval_required'] ? __('notifications::common.badges.approval_required') : __('notifications::common.badges.instant_send') }}</x-notification.chip>
                    </div>

                    <div class="mt-4">
                        <x-notification.preview-card
                            :subject-label="__('notifications::common.flows.subject')"
                            :subject="$starterFlows[$flowKey]['subject'] ?: $fallbackPreviewText"
                            :body-label="__('notifications::common.flows.body')"
                            :body="$starterFlows[$flowKey]['body'] ?: $fallbackPreviewText"
                            :meta="$starterFlows[$flowKey]['meta_items']"
                            :audience="$starterFlows[$flowKey]['audience_labels']"
                            :fallback="$fallbackPreviewText"
                            body-class="line-clamp-5"
                        />
                    </div>
                </div>
            @endforeach
        </div>
    </x-surface-card>

    <div class="grid gap-5 xl:grid-cols-2">
        @island(name: 'notification-approval-queue-overview')
        <livewire:notification.approval-queue :key="'notification-approval-queue-overview'" lazy />
        @endisland

        <x-surface-card :title="__('notifications::common.titles.template_preview')" icon="icons.layout-icon">
            <div class="space-y-3">
                @forelse ($previews['templates'] as $template)
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-zinc-950">{{ $displayTemplateKey($template->key) }}</p>
                                <p class="mt-1 text-xs uppercase tracking-tight text-zinc-400">{{ __('notifications::common.categories.'.$template->category) }} / {{ __('notifications::common.channels.'.$template->channel) }} / {{ __('notifications::common.formats.'.$template->format) }}</p>
                            </div>
                            <span class="inline-flex rounded-full border border-zinc-200 px-2.5 py-1 text-[11px] uppercase font-semibold {{ $template->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">
                                {{ $template->is_active ? __('notifications::common.badges.active') : __('notifications::common.badges.inactive') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-6 text-sm text-zinc-500">
                        {{ __('notifications::common.helpers.template_preview_none') }}
                    </div>
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('notifications::common.titles.rule_preview')" icon="icons.notification-icon">
            <div class="space-y-3">
                @forelse ($previews['rules'] as $rule)
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.categories.'.$rule->category) }} / {{ $displayTrigger($rule->trigger) }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="whitespace-nowrap rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-tight text-zinc-500">{{ __('notifications::common.channels.'.$rule->channel) }}</span>
                                    @if($rule->approval_required)
                                        <span class="whitespace-nowrap rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">{{ __('notifications::common.helpers.approval_required_short') }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="inline-flex rounded-full border border-zinc-200 px-2.5 py-1 text-[11px] uppercase font-semibold {{ $rule->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">
                                {{ $rule->is_active ? __('notifications::common.badges.active') : __('notifications::common.badges.inactive') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-6 text-sm text-zinc-500">
                        {{ __('notifications::common.helpers.rule_preview_none') }}
                    </div>
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('notifications::common.titles.queued_campaigns')" icon="icons.clock-icon">
            <div class="space-y-3">
                @forelse ($previews['campaigns'] as $campaign)
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-zinc-950">{{ $normalizeCampaignTitle($campaign->title) }}</p>
                                <p class="mt-1 text-xs uppercase tracking-tight text-zinc-400">{{ __('notifications::common.categories.'.$campaign->category) }}</p>
                            </div>
                            <div class="text-right text-xs text-zinc-500">
                                <p>{{ __('notifications::common.statuses.'.$campaign->status) }}</p>
                                <p class="mt-1">{{ __('notifications::common.statuses.'.$campaign->approval_status) }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-6 text-sm text-zinc-500">
                        {{ __('notifications::common.helpers.campaign_preview_none') }}
                    </div>
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('notifications::common.titles.failed_dispatches')" icon="icons.x-circle-icon">
            <div class="space-y-3">
                @forelse ($previews['failures'] as $dispatch)
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-zinc-950">Campaign #{{ $dispatch->campaign_id }}</p>
                                <p class="mt-1 text-xs uppercase tracking-tight text-zinc-400">{{ $dispatch->channel }}</p>
                            </div>
                            <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700">
                                {{ __('notifications::common.badges.failed') }}
                            </span>
                        </div>
                        @if ($dispatch->error_message)
                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-zinc-600">{{ $dispatch->error_message }}</p>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-6 text-sm text-zinc-500">
                        {{ __('notifications::common.helpers.failure_preview_none') }}
                    </div>
                @endforelse
            </div>
        </x-surface-card>
    </div>
</div>
