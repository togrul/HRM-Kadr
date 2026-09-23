@php
    $payload = $this->payload;
    $summary = $payload['summary'];
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    @php
        $reviewTypes = [
            'all' => __('personnel::my_hr.requests.filters.all'),
            'leave' => __('personnel::my_hr.requests.types.leave'),
            'vacation' => __('personnel::my_hr.requests.types.vacation'),
            'business_trip' => __('personnel::my_hr.requests.types.business_trip'),
            'correction' => __('personnel::my_hr.review.types.correction'),
        ];
    @endphp

    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel>
            <x-context-panel.section :title="__('personnel::my_hr.requests.fields.type')">
                @foreach ($reviewTypes as $value => $label)
                    <x-context-panel.item
                        wire:click.prevent="$set('typeFilter', '{{ $value }}')"
                        :active="$typeFilter === $value"
                        :count="$value === 'all' ? $summary['total'] : ($summary[$value] ?? 0)"
                    >{{ $label }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            @if (auth()->user()?->can('review-all-self-service-requests'))
                <x-context-panel.section :title="__('personnel::my_hr.review.labels.scope')">
                    <x-context-panel.item wire:click.prevent="$set('scopeFilter', 'mine')" :active="$scopeFilter === 'mine'">
                        {{ __('personnel::my_hr.review.scope.mine') }}
                    </x-context-panel.item>
                    <x-context-panel.item wire:click.prevent="$set('scopeFilter', 'all')" :active="$scopeFilter === 'all'">
                        {{ __('personnel::my_hr.review.scope.all') }}
                    </x-context-panel.item>
                </x-context-panel.section>
            @endif
        </x-context-panel>
    @endteleport

    <x-page-header :title="__('personnel::my_hr.review.title')" :breadcrumb="__('personnel::my_hr.review.kicker')">
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="m9 10 2 2 4-4"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$summary['total']" :label="__('personnel::my_hr.review.summary.total')" tone="amber" />
            <x-page-header.stat :value="$summary['leave']" :label="__('personnel::my_hr.requests.types.leave')" />
            <x-page-header.stat :value="$summary['vacation']" :label="__('personnel::my_hr.requests.types.vacation')" />
            <x-page-header.stat :value="$summary['business_trip']" :label="__('personnel::my_hr.requests.types.business_trip')" />
            <x-page-header.stat :value="$summary['correction']" :label="__('personnel::my_hr.review.types.correction')" />
        </x-slot:stats>

        <p class="max-w-3xl text-[12.5px] leading-relaxed text-ink-muted">{{ __('personnel::my_hr.review.description') }}</p>
    </x-page-header>

    <div class="space-y-6 px-4 py-4 sm:px-5">
    <x-ui.filter-panel>
            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.search')" labelClass="tracking-tight text-zinc-500">
                <x-ui.filter-input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('personnel::my_hr.review.messages.search_placeholder') }}" />
            </x-ui.input-shell>
            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.type')" labelClass="tracking-tight text-zinc-500">
                <x-ui.filter-native-select wire:model.live="typeFilter">
                    <option value="all">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                    <option value="leave">{{ __('personnel::my_hr.requests.types.leave') }}</option>
                    <option value="vacation">{{ __('personnel::my_hr.requests.types.vacation') }}</option>
                    <option value="business_trip">{{ __('personnel::my_hr.requests.types.business_trip') }}</option>
                    <option value="correction">{{ __('personnel::my_hr.review.types.correction') }}</option>
                </x-ui.filter-native-select>
            </x-ui.input-shell>
            @if (auth()->user()?->can('review-all-self-service-requests'))
                <x-ui.input-shell :label="__('personnel::my_hr.review.labels.scope')" labelClass="tracking-tight text-zinc-500">
                    <x-ui.filter-native-select wire:model.live="scopeFilter">
                        <option value="mine">{{ __('personnel::my_hr.review.scope.mine') }}</option>
                        <option value="all">{{ __('personnel::my_hr.review.scope.all') }}</option>
                    </x-ui.filter-native-select>
                </x-ui.input-shell>
            @endif
    </x-ui.filter-panel>

    <div class="space-y-4">
        @forelse ($payload['rows'] as $row)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1 space-y-3">
                        <div class="inline-flex max-w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-5 py-3">
                            <h3 class="max-w-[40rem] text-lg font-semibold tracking-tight text-zinc-950">{{ $row['title'] }}</h3>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold tracking-tight text-amber-700">{{ $row['status_label'] }}</span>
                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold tracking-tight text-sky-700">{{ $row['request_type_label'] }}</span>
                            <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium tracking-tight text-zinc-600">{{ $row['personnel'] }}</span>
                        </div>
                    </div>

                    <div class="rounded-full border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-semibold tracking-tight text-zinc-600">
                        {{ $row['period'] }}
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50/80 px-5 py-4 text-base leading-7 text-zinc-700">
                    {{ $row['summary'] }}
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                    @foreach ($row['details'] as $detail)
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ $detail['label'] }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $detail['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.review.labels.audit_timeline') }}</x-ui.field-label>
                        <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold tracking-tight text-zinc-600">{{ $row['request_type_label'] }}</span>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($row['audit'] as $audit)
                            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ $audit['label'] }}</x-ui.field-label>
                                <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $audit['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 border-t border-zinc-200 pt-4">
                    <x-ui.input-shell :label="__('personnel::my_hr.review.labels.review_note')" labelClass="tracking-tight text-zinc-500">
                        <x-ui.filter-textarea wire:model.live="notes.{{ $row['request_type'] }}_{{ $row['record_id'] }}" rows="3" />
                    </x-ui.input-shell>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" wire:click="approve('{{ $row['request_type'] }}', {{ $row['record_id'] }})" class="inline-flex h-9 items-center justify-center rounded-[10px] bg-ink px-4 text-[13px] font-semibold text-white transition hover:bg-ink-hover">
                            {{ __('personnel::my_hr.review.actions.approve') }}
                        </button>
                        <button type="button" wire:click="reject('{{ $row['request_type'] }}', {{ $row['record_id'] }})" class="inline-flex h-9 items-center justify-center rounded-[10px] px-4 text-[13px] font-semibold text-ink-muted transition hover:bg-[#ffe4e6] hover:text-[#be123c]">
                            {{ __('personnel::my_hr.review.actions.reject') }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <x-ui.empty-state
                icon="icons.comment-icon"
                :title="__('personnel::my_hr.review.empty.title')"
                :message="__('personnel::my_hr.review.empty.body')"
                class="py-12"
            />
        @endforelse
    </div>
</div>
</div>
