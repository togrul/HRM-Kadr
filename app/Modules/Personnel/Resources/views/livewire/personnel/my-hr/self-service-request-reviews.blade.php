@php
    $payload = $this->payload;
    $summary = $payload['summary'];
@endphp

<div class="space-y-6 px-6 py-6">
    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.review.kicker') }}</x-ui.field-label>
            <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.review.title') }}</h1>
            <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.review.description') }}</p>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => __('personnel::my_hr.review.summary.total'), 'value' => $summary['total']],
                ['label' => __('personnel::my_hr.requests.types.leave'), 'value' => $summary['leave']],
                ['label' => __('personnel::my_hr.requests.types.vacation'), 'value' => $summary['vacation']],
                ['label' => __('personnel::my_hr.requests.types.business_trip'), 'value' => $summary['business_trip']],
                ['label' => __('personnel::my_hr.review.types.correction'), 'value' => $summary['correction']],
            ] as $card)
                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ $card['label'] }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="grid gap-1 lg:grid-cols-3 2xl:grid-cols-4">
            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.search')" labelClass="tracking-tight text-zinc-500">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('personnel::my_hr.review.messages.search_placeholder') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none" />
            </x-ui.input-shell>
            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.type')" labelClass="tracking-tight text-zinc-500">
                <select wire:model.live="typeFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                    <option value="all">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                    <option value="leave">{{ __('personnel::my_hr.requests.types.leave') }}</option>
                    <option value="vacation">{{ __('personnel::my_hr.requests.types.vacation') }}</option>
                    <option value="business_trip">{{ __('personnel::my_hr.requests.types.business_trip') }}</option>
                    <option value="correction">{{ __('personnel::my_hr.review.types.correction') }}</option>
                </select>
            </x-ui.input-shell>
            @if (auth()->user()?->can('review-all-self-service-requests'))
                <x-ui.input-shell :label="__('personnel::my_hr.review.labels.scope')" labelClass="tracking-tight text-zinc-500">
                    <select wire:model.live="scopeFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                        <option value="mine">{{ __('personnel::my_hr.review.scope.mine') }}</option>
                        <option value="all">{{ __('personnel::my_hr.review.scope.all') }}</option>
                    </select>
                </x-ui.input-shell>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($payload['rows'] as $row)
            <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1 space-y-3">
                        <div class="inline-flex max-w-full rounded-[24px] border border-zinc-200 bg-zinc-50 px-5 py-3">
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

                <div class="mt-4 rounded-[24px] border border-zinc-200 bg-zinc-50/80 px-5 py-4 text-base leading-7 text-zinc-700">
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

                <div class="mt-4 rounded-[24px] border border-zinc-200 bg-zinc-50/70 px-4 py-4">
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
                        <textarea wire:model.live="notes.{{ $row['request_type'] }}_{{ $row['record_id'] }}" rows="3" class="w-full rounded-3xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none"></textarea>
                    </x-ui.input-shell>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" wire:click="approve('{{ $row['request_type'] }}', {{ $row['record_id'] }})" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold tracking-tight text-emerald-700 transition hover:bg-emerald-100">
                            {{ __('personnel::my_hr.review.actions.approve') }}
                        </button>
                        <button type="button" wire:click="reject('{{ $row['request_type'] }}', {{ $row['record_id'] }})" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold tracking-tight text-rose-700 transition hover:bg-rose-100">
                            {{ __('personnel::my_hr.review.actions.reject') }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-[28px] border border-dashed border-zinc-300 bg-white px-6 py-12 text-center shadow-sm">
                <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.review.empty.title') }}</h3>
                <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.review.empty.body') }}</p>
            </div>
        @endforelse
    </div>
</div>
