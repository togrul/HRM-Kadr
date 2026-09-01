@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $eventTotal = (int) $eventCounts->sum();
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('audit::activity.header.title')"
            :subtitle="__('audit::activity.header.kicker')"
        >
            <x-context-panel.section :title="__('audit::activity.filters.event')">
                <x-context-panel.item
                    wire:click.prevent="$set('event', '')"
                    :active="$event === ''"
                    :count="$num($eventTotal)"
                >{{ __('audit::activity.filters.all') }}</x-context-panel.item>

                @foreach ($eventCounts->except('') as $option => $count)
                    <x-context-panel.item
                        wire:key="audit-panel-event-{{ $option }}"
                        wire:click.prevent="$set('event', '{{ $option }}')"
                        :active="$event === $option"
                        :dot="$this->eventDot($option)"
                        :count="$num($count)"
                    >{{ $this->eventLabel($option) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            {{-- The period narrows every number on the screen, so it belongs beside the
                 facet rather than in a toolbar the table scrolls away from. --}}
            <x-context-panel.section :title="__('audit::activity.filters.period')" :padded="false">
                <div class="space-y-2.5 px-3.5 pb-3.5 pt-1">
                    <label class="block">
                        <span class="hrm-eyebrow block pb-1">{{ __('audit::activity.filters.from') }}</span>
                        <x-ui.input type="date" wire:model.live="dateFrom" />
                    </label>
                    <label class="block">
                        <span class="hrm-eyebrow block pb-1">{{ __('audit::activity.filters.to') }}</span>
                        <x-ui.input type="date" wire:model.live="dateTo" />
                    </label>
                </div>

                <x-slot name="footer">
                    <p class="text-[11px] leading-snug text-ink-faint">{{ __('audit::activity.labels.read_only_note') }}</p>
                </x-slot>
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('audit::activity.header.title')"
        :breadcrumb="__('audit::activity.header.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <span class="hidden text-[11.5px] text-ink-faint sm:inline">{{ __('audit::activity.labels.read_only_short') }}</span>

            <label class="flex items-center gap-1.5">
                <span class="text-[11.5px] text-ink-faint">{{ __('audit::activity.filters.per_page') }}</span>
                <x-ui.select wire:model.live="perPage" class="w-[86px]">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </x-ui.select>
            </label>

            <x-pill-button variant="emerald" :icon="true" :href="$this->exportUrl('xlsx')"
                title="{{ __('audit::activity.actions.export_xlsx') }}">
                <x-icons.excel-icon />
            </x-pill-button>

            <x-pill-button :href="$this->exportUrl('csv')">
                {{ __('audit::activity.actions.export_csv') }}
            </x-pill-button>
        </x-slot:actions>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <x-ui.metric-tile :label="__('audit::activity.metrics.total')" :value="$num($summary['total'])" />
            <x-ui.metric-tile :label="__('audit::activity.metrics.today')" :value="$num($summary['today'])" tone="green" />
            <x-ui.metric-tile :label="__('audit::activity.metrics.profile_opened')" :value="$num($summary['profile_opened'])" tone="blue" />
            <x-ui.metric-tile :label="__('audit::activity.metrics.users')" :value="$num($summary['users'])" tone="violet" />
        </section>

        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 leading-tight">
                    <p class="hrm-eyebrow">{{ __('audit::activity.list.kicker') }}</p>
                    <p class="mt-0.5 text-[13.5px] font-semibold tracking-[-0.02em] text-ink">
                        {{ __('audit::activity.list.total', ['count' => $num($activities->total())]) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="w-full sm:w-[280px]">
                        <x-ui.input
                            icon="search"
                            wire:model.live.debounce.350ms="search"
                            placeholder="{{ __('audit::activity.filters.search_placeholder') }}"
                        />
                    </div>

                    <x-ui.select wire:model.live="logName" class="w-[150px]">
                        <option value="">{{ __('audit::activity.filters.log_name') }}</option>
                        @foreach ($logNameOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-pill-button wire:click="resetFilters" wire:loading.attr="disabled"
                        title="{{ __('audit::activity.actions.reset_filters') }}">
                        {{ __('audit::activity.actions.reset_short') }}
                    </x-pill-button>
                </div>
            </div>

            <x-table.tbl :headers="[
                __('audit::activity.table.time'),
                __('audit::activity.table.event'),
                __('audit::activity.table.description'),
                __('audit::activity.table.actor'),
                __('audit::activity.table.subject'),
                __('audit::activity.table.action'),
            ]">
                @forelse ($activities as $activity)
                    <tr wire:key="audit-row-{{ $activity->id }}">
                        <x-table.td>
                            <span class="hrm-num block text-[13px] font-medium text-ink">{{ $activity->created_at?->format('d.m.Y') }}</span>
                            <span class="hrm-num block text-[11px] text-ink-faint">{{ $activity->created_at?->format('H:i:s') }}</span>
                        </x-table.td>

                        <x-table.td>
                            <x-small-badge :mode="$this->eventTone($activity->event)" dot>
                                {{ $this->eventLabel($activity->event) }}
                            </x-small-badge>
                            <span class="mt-1 block text-[11px] text-ink-faint">{{ $activity->log_name ?: __('audit::activity.labels.no_log_name') }}</span>
                        </x-table.td>

                        <x-table.td :standart-width="true" extra-classes="min-w-[260px] max-w-[380px]">
                            <span class="line-clamp-2 text-[13px] text-ink-soft">{{ $this->descriptionLabel($activity->description) }}</span>
                        </x-table.td>

                        <x-table.td :standart-width="true" extra-classes="min-w-[180px] max-w-[260px]">
                            <span class="line-clamp-2 text-[13px] text-ink-soft">{{ $this->actorLabel($activity) }}</span>
                        </x-table.td>

                        <x-table.td :standart-width="true" extra-classes="min-w-[180px] max-w-[260px]">
                            <span class="line-clamp-2 text-[13px] text-ink-muted">{{ $this->subjectLabel($activity) }}</span>
                        </x-table.td>

                        <x-table.td :is-button="true">
                            <x-pill-button
                                wire:key="audit-open-{{ $activity->id }}"
                                wire:click.stop="selectActivity({{ $activity->id }})"
                                wire:loading.attr="disabled"
                                title="{{ __('audit::activity.actions.open_detail') }}"
                            >{{ __('audit::activity.table.action') }}</x-pill-button>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10">
                            <x-ui.empty-state icon="icons.document-icon" :message="__('audit::activity.empty.title')" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>

            <x-pagination :paginator="$activities" :unit="__('audit::activity.labels.results_unit')" />
        </section>
    </div>

    @if ($selectedActivity)
        <x-ui.side-panel
            title-id="audit-detail-title"
            close-action="$wire.closeDetail()"
            :close-label="__('audit::activity.actions.close_detail')"
            width="3xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-hairline-subtle px-5 py-4">
                <div class="min-w-0">
                    <p class="hrm-eyebrow">{{ __('audit::activity.detail.kicker') }}</p>
                    <h2 id="audit-detail-title" class="hrm-num mt-1.5 text-[17px] font-semibold tracking-[-0.025em] text-ink">#{{ $selectedActivity->id }}</h2>
                    <p class="hrm-num mt-1 text-[11.5px] text-ink-faint">{{ $selectedActivity->created_at?->format('d.m.Y H:i:s') }}</p>
                </div>

                <x-pill-button x-ref="closeButton" :icon="true" x-on:click="close()"
                    title="{{ __('audit::activity.actions.close_detail') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </x-pill-button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <div class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3">
                    <p class="hrm-eyebrow">{{ __('audit::activity.detail.description') }}</p>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-ink">{{ $this->descriptionLabel($selectedActivity->description) }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <x-fact-tile :label="__('audit::activity.detail.log_name')" :value="$selectedActivity->log_name ?: '—'" />
                    <x-fact-tile :label="__('audit::activity.detail.event')" :value="$this->eventLabel($selectedActivity->event)" />
                    <x-fact-tile :label="__('audit::activity.detail.actor')" :value="$this->actorLabel($selectedActivity)" />
                    <x-fact-tile :label="__('audit::activity.detail.subject')" :value="$this->subjectLabel($selectedActivity)" />
                </div>

                <div>
                    <p class="hrm-eyebrow">{{ __('audit::activity.detail.properties') }}</p>
                    <div class="mt-2 space-y-2">
                        @forelse ($this->propertyRows($selectedActivity) as $row)
                            <div class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3">
                                <p class="text-[11.5px] font-medium text-ink-muted">{{ $row['key'] }}</p>
                                <pre class="mt-1.5 max-h-48 overflow-auto whitespace-pre-wrap break-words text-[12px] leading-5 text-ink-soft">{{ $row['value'] }}</pre>
                            </div>
                        @empty
                            <x-ui.empty-state icon="icons.document-icon" :message="__('audit::activity.detail.no_properties')" />
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-hairline-subtle bg-white px-5 py-3">
                <x-pill-button variant="primary" x-on:click="close()">
                    {{ __('audit::activity.actions.close_short') }}
                </x-pill-button>
            </div>
        </x-ui.side-panel>
    @endif
</div>
