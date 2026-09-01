@php
    $payload = $this->payload;
    $summary = $payload['summary'];
    $rows = $payload['rows'];

    $createForms = ['leave', 'vacation', 'business_trip'];
    $labelClass = 'hrm-eyebrow block pb-1';

    $statusTone = fn (string $mode): string => match ($mode) {
        'warning' => 'amber',
        'success' => 'green',
        'info' => 'blue',
        'danger' => 'rose',
        default => 'secondary',
    };

    $iconTone = fn (string $mode): string => match ($mode) {
        'warning' => 'bg-[#fef3c7] text-[#b45309]',
        'success' => 'bg-[#d1fae5] text-[#047857]',
        'info' => 'bg-[#e0f2fe] text-[#0369a1]',
        'danger' => 'bg-[#ffe4e6] text-[#be123c]',
        default => 'bg-[#f4f4f5] text-[#52525b]',
    };

    $hasFilters = $search !== '' || $typeFilter !== 'all' || $statusFilter !== 'all' || filled($dateFrom) || filled($dateTo);
@endphp

<div
    class="flex flex-col gap-4"
    x-data
    x-on:my-hr-correction-form-opened.window="
        requestAnimationFrame(() => {
            $refs.correctionFormCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    "
>
    {{-- ===================== toolbar ===================== --}}
    <section class="rounded-xl border border-hairline bg-white">
        <div wire:key="my-hr-request-create-switcher-{{ $activeCreateForm ?: 'none' }}"
            class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            {{-- the four numbers as one quiet line instead of four competing cards --}}
            <div class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1 text-[12px] text-ink-faint">
                <span><span class="hrm-num text-[13px] font-semibold text-ink">{{ $summary['total'] }}</span> {{ __('personnel::my_hr.requests.summary.total') }}</span>
                <span class="hidden h-3 w-px bg-hairline sm:block"></span>
                <span><span class="hrm-num text-[13px] font-semibold text-[#b45309]">{{ $summary['pending'] }}</span> {{ __('personnel::my_hr.requests.summary.pending') }}</span>
                <span><span class="hrm-num text-[13px] font-semibold text-[#0369a1]">{{ $summary['active'] }}</span> {{ __('personnel::my_hr.requests.summary.active') }}</span>
                <span><span class="hrm-num text-[13px] font-semibold text-[#047857]">{{ $summary['completed'] }}</span> {{ __('personnel::my_hr.requests.summary.completed') }}</span>
            </div>

            <x-filter.nav wrap class="min-w-0 shrink-0">
                @foreach ($createForms as $form)
                    <x-filter.item
                        wire:key="my-hr-request-create-{{ $form }}-{{ $activeCreateForm ?: 'none' }}"
                        wire:click.prevent="openCreateForm('{{ $form }}')"
                        wire:loading.attr="disabled"
                        :active="$activeCreateForm === $form"
                    >{{ __('personnel::my_hr.requests.actions.create_'.$form) }}</x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>

        <div class="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-5">
            <label class="min-w-0">
                <span class="{{ $labelClass }}">{{ __('personnel::my_hr.requests.fields.search') }}</span>
                <x-ui.input wire:model.live.debounce.300ms="search" type="search" icon="search"
                    placeholder="{{ __('personnel::my_hr.requests.messages.search_placeholder') }}" />
            </label>

            <label class="min-w-0">
                <span class="{{ $labelClass }}">{{ __('personnel::my_hr.requests.fields.type') }}</span>
                <x-ui.select wire:model.live="typeFilter">
                    <option value="all">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                    @foreach ($createForms as $type)
                        <option value="{{ $type }}">{{ __('personnel::my_hr.requests.types.'.$type) }}</option>
                    @endforeach
                </x-ui.select>
            </label>

            <label class="min-w-0">
                <span class="{{ $labelClass }}">{{ __('personnel::my_hr.requests.fields.status') }}</span>
                <x-ui.select wire:model.live="statusFilter">
                    <option value="all">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                    @foreach (['pending', 'approved', 'upcoming', 'active', 'completed', 'cancelled', 'deleted'] as $status)
                        <option value="{{ $status }}">{{ __('personnel::my_hr.requests.status.'.$status) }}</option>
                    @endforeach
                </x-ui.select>
            </label>

            <div class="grid min-w-0 gap-3 sm:grid-cols-2 xl:col-span-2">
                <label class="min-w-0">
                    <span class="{{ $labelClass }}">{{ __('personnel::my_hr.requests.fields.date_from') }}</span>
                    <x-ui.input wire:model.live="dateFrom" type="date" class="hrm-num" />
                </label>
                <label class="min-w-0">
                    <span class="{{ $labelClass }}">{{ __('personnel::my_hr.requests.fields.date_to') }}</span>
                    <x-ui.input wire:model.live="dateTo" type="date" class="hrm-num" />
                </label>
            </div>
        </div>
    </section>

    {{-- ===================== create form ===================== --}}
    @if ($activeCreateForm !== '')
        <section wire:key="my-hr-request-active-form-{{ $activeCreateForm }}" class="rounded-xl border border-hairline bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <div class="min-w-0">
                    <p class="hrm-eyebrow">{{ __('personnel::my_hr.requests.kicker') }}</p>
                    <h3 class="mt-1 text-[13.5px] font-semibold tracking-[-0.02em] text-ink">
                        {{ __('personnel::my_hr.requests.actions.create_'.$activeCreateForm) }}
                    </h3>
                </div>

                <x-pill-button wire:click="cancelCreateForm" wire:loading.attr="disabled" wire:target="cancelCreateForm">
                    {{ __('personnel::my_hr.requests.actions.cancel_form') }}
                </x-pill-button>
            </div>

            @if ($activeCreateForm === 'leave')
                <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.leave_type')" :error="$errors->first('leaveForm.leave_type_id')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.select wire:model.live="leaveForm.leave_type_id">
                            <option value="">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                            @foreach ($this->leaveTypeOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.input-shell>

                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.duration_unit')" :error="$errors->first('leaveForm.duration_unit')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.select wire:model.live="leaveForm.duration_unit">
                            @foreach (['day', 'half_day', 'hour'] as $unit)
                                <option value="{{ $unit }}">{{ __('personnel::my_hr.requests.duration_units.'.$unit) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.input-shell>

                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('leaveForm.starts_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="leaveForm.starts_at" type="date" class="hrm-num" />
                    </x-ui.input-shell>

                    @if (($leaveForm['duration_unit'] ?? 'day') === 'day')
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('leaveForm.ends_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model.live="leaveForm.ends_at" type="date" class="hrm-num" />
                        </x-ui.input-shell>
                    @elseif (($leaveForm['duration_unit'] ?? 'day') === 'half_day')
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.partial_day_part')" :error="$errors->first('leaveForm.partial_day_part')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.select wire:model.live="leaveForm.partial_day_part">
                                <option value="">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                                <option value="first_half">{{ __('personnel::my_hr.requests.partial_day_parts.first_half') }}</option>
                                <option value="second_half">{{ __('personnel::my_hr.requests.partial_day_parts.second_half') }}</option>
                            </x-ui.select>
                        </x-ui.input-shell>
                    @else
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_time')" :error="$errors->first('leaveForm.starts_time')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model.live="leaveForm.starts_time" type="time" class="hrm-num" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_time')" :error="$errors->first('leaveForm.ends_time')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model.live="leaveForm.ends_time" type="time" class="hrm-num" />
                        </x-ui.input-shell>
                    @endif

                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.reason')" :error="$errors->first('leaveForm.reason')" containerClass="!space-y-0 sm:col-span-2" :labelClass="$labelClass">
                        <x-ui.textarea wire:model.live="leaveForm.reason" :rows="3" />
                    </x-ui.input-shell>

                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.supporting_document')" :error="$errors->first('leaveDocument')" containerClass="!space-y-0 sm:col-span-2" :labelClass="$labelClass">
                        <input wire:model.live="leaveDocument" type="file"
                            class="block h-9 w-full min-w-0 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-1.5 text-[12.5px] text-ink-muted shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-ink file:px-3 file:py-1 file:text-[12px] file:font-semibold file:text-white" />
                    </x-ui.input-shell>
                </div>

                <div class="flex justify-end border-t border-hairline-subtle px-4 py-3">
                    <x-pill-button variant="primary" wire:click="storeLeaveRequest" wire:loading.attr="disabled" wire:target="storeLeaveRequest">
                        {{ __('personnel::my_hr.requests.actions.submit_leave') }}
                    </x-pill-button>
                </div>
            @elseif ($activeCreateForm === 'vacation')
                <div class="grid gap-3 p-4 sm:grid-cols-3">
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.destination')" :error="$errors->first('vacationForm.vacation_places')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="vacationForm.vacation_places" type="text" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('vacationForm.start_date')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="vacationForm.start_date" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('vacationForm.end_date')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="vacationForm.end_date" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                </div>

                <div class="flex justify-end border-t border-hairline-subtle px-4 py-3">
                    <x-pill-button variant="primary" wire:click="storeVacationRequest" wire:loading.attr="disabled" wire:target="storeVacationRequest">
                        {{ __('personnel::my_hr.requests.actions.submit_vacation') }}
                    </x-pill-button>
                </div>
            @else
                <div class="grid gap-3 p-4 sm:grid-cols-3">
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.location')" :error="$errors->first('businessTripForm.location')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="businessTripForm.location" type="text" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('businessTripForm.start_date')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="businessTripForm.start_date" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('businessTripForm.end_date')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="businessTripForm.end_date" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.description')" :error="$errors->first('businessTripForm.description')" containerClass="!space-y-0 sm:col-span-3" :labelClass="$labelClass">
                        <x-ui.textarea wire:model.live="businessTripForm.description" :rows="3" />
                    </x-ui.input-shell>
                </div>

                <div class="flex justify-end border-t border-hairline-subtle px-4 py-3">
                    <x-pill-button variant="primary" wire:click="storeBusinessTripRequest" wire:loading.attr="disabled" wire:target="storeBusinessTripRequest">
                        {{ __('personnel::my_hr.requests.actions.submit_business_trip') }}
                    </x-pill-button>
                </div>
            @endif
        </section>
    @endif

    {{-- ===================== history ===================== --}}
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
            <h3 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::my_hr.requests.title') }}</h3>
            <span class="hrm-num shrink-0 text-[11.5px] text-ink-faint">{{ count($rows) }}</span>
        </div>

        <div class="divide-y divide-hairline-subtle">
            @forelse ($rows as $row)
                {{-- one line per request; the detail opens on demand instead of four tiles always on screen --}}
                <div wire:key="my-hr-request-{{ $row['id'] }}" x-data="{ open: false }">
                    <button
                        type="button"
                        x-on:click="open = ! open"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-[#fafafa]"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[11px] {{ $iconTone($row['status_mode']) }}">
                            @if ($row['request_type'] === 'leave')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            @elseif ($row['request_type'] === 'business_trip')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2 16 11l3.5-3.5a2.1 2.1 0 0 0-3-3L13 8 4.8 6.2a.5.5 0 0 0-.5.8l3.2 4-2 2-2.3-.6a.5.5 0 0 0-.5.8L5 16l1.8 2.3a.5.5 0 0 0 .8-.5l-.6-2.3 2-2 4 3.2a.5.5 0 0 0 .8-.5Z"/></svg>
                            @else
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            @endif
                        </span>

                        <span class="min-w-0 flex-1 leading-tight">
                            <span class="block truncate text-[13px] font-medium text-ink">{{ $row['title'] }}</span>
                            <span class="hrm-num mt-0.5 block truncate text-[11.5px] text-ink-faint">{{ $row['period'] }}</span>
                        </span>

                        <x-small-badge mode="secondary" class="hidden sm:inline-flex">{{ $row['type_label'] }}</x-small-badge>
                        <x-small-badge :mode="$statusTone($row['status_mode'])" dot>{{ $row['status_label'] }}</x-small-badge>

                        <svg class="h-4 w-4 shrink-0 text-ink-faint transition-transform" x-bind:class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <div x-show="open" x-collapse x-cloak class="border-t border-hairline-subtle bg-[#fafafa] px-4 py-3">
                        <p class="text-[12.5px] leading-relaxed text-ink-muted">{{ $row['summary'] }}</p>

                        <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($row['details'] as $detail)
                                <x-fact-tile :label="$detail['label']" :value="$detail['value']" class="bg-white" />
                            @endforeach
                        </div>

                        @if (($row['can_request_correction'] ?? false) === true)
                            <div class="mt-3">
                                <x-pill-button wire:click="openCorrectionForm('{{ $row['request_type'] }}', {{ $row['record_id'] }})"
                                    wire:loading.attr="disabled" wire:target="openCorrectionForm">
                                    {{ __('personnel::my_hr.requests.actions.request_correction') }}
                                </x-pill-button>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-3">
                    <x-ui.empty-state icon="icons.comment-icon"
                        :title="__('personnel::my_hr.requests.empty.title')"
                        :message="$hasFilters ? __('personnel::my_hr.requests.empty.body') : __('personnel::my_hr.requests.description')" />
                </div>
            @endforelse
        </div>
    </section>

    {{-- ===================== correction request ===================== --}}
    @if ($showCorrectionForm)
        <section x-ref="correctionFormCard" class="rounded-xl border border-hairline bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <div class="min-w-0">
                    <p class="hrm-eyebrow">{{ __('personnel::my_hr.review.types.correction') }}</p>
                    <h3 class="mt-1 text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::my_hr.requests.actions.request_correction') }}</h3>
                </div>

                <x-pill-button wire:click="cancelCorrectionForm" wire:loading.attr="disabled" wire:target="cancelCorrectionForm">
                    {{ __('personnel::my_hr.requests.actions.cancel_form') }}
                </x-pill-button>
            </div>

            <div class="grid gap-3 p-4 sm:grid-cols-3">
                @if ($correctionRequestType === 'leave')
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('correctionForm.starts_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="correctionForm.starts_at" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('correctionForm.ends_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="correctionForm.ends_at" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                @elseif ($correctionRequestType === 'vacation')
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.destination')" :error="$errors->first('correctionForm.vacation_places')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="correctionForm.vacation_places" type="text" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('correctionForm.start_date')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="correctionForm.start_date" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('correctionForm.end_date')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="correctionForm.end_date" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                @else
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.location')" :error="$errors->first('correctionForm.location')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="correctionForm.location" type="text" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('correctionForm.start_date')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="correctionForm.start_date" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('correctionForm.end_date')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model.live="correctionForm.end_date" type="date" class="hrm-num" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.description')" :error="$errors->first('correctionForm.description')" containerClass="!space-y-0 sm:col-span-3" :labelClass="$labelClass">
                        <x-ui.textarea wire:model.live="correctionForm.description" :rows="3" />
                    </x-ui.input-shell>
                @endif

                <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.reason')" :error="$errors->first('correctionForm.reason')" containerClass="!space-y-0 sm:col-span-3" :labelClass="$labelClass">
                    <x-ui.textarea wire:model.live="correctionForm.reason" :rows="3" />
                </x-ui.input-shell>
            </div>

            <div class="flex justify-end border-t border-hairline-subtle px-4 py-3">
                <x-pill-button variant="primary" wire:click="storeCorrectionRequest" wire:loading.attr="disabled" wire:target="storeCorrectionRequest">
                    {{ __('personnel::my_hr.requests.actions.submit_correction') }}
                </x-pill-button>
            </div>
        </section>
    @endif
</div>
