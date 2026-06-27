@php
    $payload = $this->payload;
    $summary = $payload['summary'];
    $rows = $payload['rows'];
    $statCards = [
        ['label' => __('personnel::my_hr.requests.summary.total'), 'value' => $summary['total'], 'accent' => 'bg-zinc-400'],
        ['label' => __('personnel::my_hr.requests.summary.pending'), 'value' => $summary['pending'], 'accent' => 'bg-amber-400'],
        ['label' => __('personnel::my_hr.requests.summary.active'), 'value' => $summary['active'], 'accent' => 'bg-sky-500'],
        ['label' => __('personnel::my_hr.requests.summary.completed'), 'value' => $summary['completed'], 'accent' => 'bg-emerald-500'],
    ];
@endphp

<div
    class="space-y-6"
    x-data
    x-on:my-hr-correction-form-opened.window="
        requestAnimationFrame(() => {
            $refs.correctionFormCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    "
>
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div wire:key="my-hr-request-create-switcher-{{ $activeCreateForm ?: 'none' }}" class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.requests.kicker') }}</x-ui.field-label>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.requests.title') }}</h2>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.requests.description') }}</p>
            </div>

            <div class="w-full lg:max-w-2xl">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-1.5 shadow-sm">
                    <div class="grid gap-1.5 sm:grid-cols-3">
                        <button wire:key="my-hr-request-create-leave-{{ $activeCreateForm ?: 'none' }}" type="button" wire:click="openCreateForm('leave')" @class([
                            'flex items-center justify-center rounded-xl px-4 py-2.5 text-center text-sm font-semibold tracking-tight transition-all duration-200',
                            'bg-white text-zinc-950 shadow-sm ring-1 ring-zinc-200' => $activeCreateForm === 'leave',
                            'text-zinc-500 hover:bg-white/70 hover:text-zinc-900' => $activeCreateForm !== 'leave',
                        ])>
                            {{ __('personnel::my_hr.requests.actions.create_leave') }}
                        </button>
                        <button wire:key="my-hr-request-create-vacation-{{ $activeCreateForm ?: 'none' }}" type="button" wire:click="openCreateForm('vacation')" @class([
                            'flex items-center justify-center rounded-xl px-4 py-2.5 text-center text-sm font-semibold tracking-tight transition-all duration-200',
                            'bg-white text-zinc-950 shadow-sm ring-1 ring-zinc-200' => $activeCreateForm === 'vacation',
                            'text-zinc-500 hover:bg-white/70 hover:text-zinc-900' => $activeCreateForm !== 'vacation',
                        ])>
                            {{ __('personnel::my_hr.requests.actions.create_vacation') }}
                        </button>
                        <button wire:key="my-hr-request-create-business-trip-{{ $activeCreateForm ?: 'none' }}" type="button" wire:click="openCreateForm('business_trip')" @class([
                            'flex items-center justify-center rounded-xl px-4 py-2.5 text-center text-sm font-semibold tracking-tight transition-all duration-200',
                            'bg-white text-zinc-950 shadow-sm ring-1 ring-zinc-200' => $activeCreateForm === 'business_trip',
                            'text-zinc-500 hover:bg-white/70 hover:text-zinc-900' => $activeCreateForm !== 'business_trip',
                        ])>
                            {{ __('personnel::my_hr.requests.actions.create_business_trip') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($statCards as $card)
                <div class="group rounded-2xl border border-zinc-200 bg-white px-5 py-4 shadow-sm transition hover:border-zinc-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">{{ $card['label'] }}</span>
                        <span class="h-2 w-2 rounded-full {{ $card['accent'] }}"></span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        @if ($activeCreateForm !== '')
            <div wire:key="my-hr-request-active-form-{{ $activeCreateForm }}" class="mt-6 rounded-[28px] border border-zinc-200 bg-zinc-50/80 p-5 shadow-sm">
                <div class="flex flex-col gap-3 border-b border-zinc-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.requests.kicker') }}</x-ui.field-label>
                        <h3 class="text-xl font-semibold tracking-tight text-zinc-950">
                            @if ($activeCreateForm === 'leave')
                                {{ __('personnel::my_hr.requests.actions.create_leave') }}
                            @elseif ($activeCreateForm === 'vacation')
                                {{ __('personnel::my_hr.requests.actions.create_vacation') }}
                            @else
                                {{ __('personnel::my_hr.requests.actions.create_business_trip') }}
                            @endif
                        </h3>
                    </div>

                    <button type="button" wire:click="cancelCreateForm" class="rounded-2xl bg-[#f5f5f7] px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">
                        {{ __('personnel::my_hr.requests.actions.cancel_form') }}
                    </button>
                </div>

                @if ($activeCreateForm === 'leave')
                    <div class="mt-5 space-y-5">
                        <div class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-4">
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.leave_type')" :error="$errors->first('leaveForm.leave_type_id')" labelClass="tracking-tight text-zinc-500">
                                <select wire:model.live="leaveForm.leave_type_id" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0">
                                    <option value="">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                                    @foreach ($this->leaveTypeOptions as $option)
                                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.duration_unit')" :error="$errors->first('leaveForm.duration_unit')" labelClass="tracking-tight text-zinc-500">
                                <select wire:model.live="leaveForm.duration_unit" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0">
                                    @foreach (['day', 'half_day', 'hour'] as $unit)
                                        <option value="{{ $unit }}">{{ __('personnel::my_hr.requests.duration_units.'.$unit) }}</option>
                                    @endforeach
                                </select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('leaveForm.starts_at')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="leaveForm.starts_at" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                            </x-ui.input-shell>
                            @if (($leaveForm['duration_unit'] ?? 'day') === 'day')
                                <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('leaveForm.ends_at')" labelClass="tracking-tight text-zinc-500">
                                    <input wire:model.live="leaveForm.ends_at" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                                </x-ui.input-shell>
                            @elseif (($leaveForm['duration_unit'] ?? 'day') === 'half_day')
                                <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.partial_day_part')" :error="$errors->first('leaveForm.partial_day_part')" labelClass="tracking-tight text-zinc-500">
                                    <select wire:model.live="leaveForm.partial_day_part" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0">
                                        <option value="">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                                        <option value="first_half">{{ __('personnel::my_hr.requests.partial_day_parts.first_half') }}</option>
                                        <option value="second_half">{{ __('personnel::my_hr.requests.partial_day_parts.second_half') }}</option>
                                    </select>
                                </x-ui.input-shell>
                            @else
                                <div class="grid gap-3 sm:grid-cols-2 lg:col-span-2">
                                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_time')" :error="$errors->first('leaveForm.starts_time')" labelClass="tracking-tight text-zinc-500">
                                        <input wire:model.live="leaveForm.starts_time" type="time" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                                    </x-ui.input-shell>
                                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_time')" :error="$errors->first('leaveForm.ends_time')" labelClass="tracking-tight text-zinc-500">
                                        <input wire:model.live="leaveForm.ends_time" type="time" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                                    </x-ui.input-shell>
                                </div>
                            @endif
                        </div>

                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.reason')" :error="$errors->first('leaveForm.reason')" labelClass="tracking-tight text-zinc-500">
                            <textarea wire:model.live="leaveForm.reason" rows="4" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold leading-6 text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0"></textarea>
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.supporting_document')" :error="$errors->first('leaveDocument')" labelClass="tracking-tight text-zinc-500">
                            <div class="rounded-[24px] bg-[#f5f5f7] px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_8px_18px_rgba(0,0,0,0.035)]">
                                <input wire:model.live="leaveDocument" type="file" class="block w-full text-sm text-zinc-700 file:mr-4 file:rounded-2xl file:border-0 file:bg-zinc-950 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-zinc-800" />
                            </div>
                        </x-ui.input-shell>

                        <div class="flex justify-end">
                            <button type="button" wire:click="storeLeaveRequest" class="rounded-2xl bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800">
                                {{ __('personnel::my_hr.requests.actions.submit_leave') }}
                            </button>
                        </div>
                    </div>
                @elseif ($activeCreateForm === 'vacation')
                    <div class="mt-5 space-y-5">
                        <div class="grid gap-3 lg:grid-cols-3">
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.destination')" :error="$errors->first('vacationForm.vacation_places')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="vacationForm.vacation_places" type="text" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('vacationForm.start_date')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="vacationForm.start_date" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('vacationForm.end_date')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="vacationForm.end_date" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                            </x-ui.input-shell>
                        </div>

                        <div class="flex justify-end">
                            <button type="button" wire:click="storeVacationRequest" class="rounded-2xl bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800">
                                {{ __('personnel::my_hr.requests.actions.submit_vacation') }}
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mt-5 space-y-5">
                        <div class="grid gap-3 lg:grid-cols-3">
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.location')" :error="$errors->first('businessTripForm.location')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="businessTripForm.location" type="text" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('businessTripForm.start_date')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="businessTripForm.start_date" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('businessTripForm.end_date')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="businessTripForm.end_date" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                            </x-ui.input-shell>
                        </div>

                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.description')" :error="$errors->first('businessTripForm.description')" labelClass="tracking-tight text-zinc-500">
                            <textarea wire:model.live="businessTripForm.description" rows="4" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold leading-6 text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0"></textarea>
                        </x-ui.input-shell>

                        <div class="flex justify-end">
                            <button type="button" wire:click="storeBusinessTripRequest" class="rounded-2xl bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800">
                                {{ __('personnel::my_hr.requests.actions.submit_business_trip') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50/60 p-5 shadow-sm">
        <div class="grid gap-1 lg:grid-cols-2 2xl:grid-cols-4">
            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.search')" labelClass="tracking-tight text-zinc-500">
                <x-ui.filter-input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('personnel::my_hr.requests.messages.search_placeholder') }}" />
            </x-ui.input-shell>
            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.type')" labelClass="tracking-tight text-zinc-500">
                <x-ui.filter-native-select wire:model.live="typeFilter">
                    <option value="all">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                    <option value="leave">{{ __('personnel::my_hr.requests.types.leave') }}</option>
                    <option value="vacation">{{ __('personnel::my_hr.requests.types.vacation') }}</option>
                    <option value="business_trip">{{ __('personnel::my_hr.requests.types.business_trip') }}</option>
                </x-ui.filter-native-select>
            </x-ui.input-shell>
            <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.status')" labelClass="tracking-tight text-zinc-500">
                <x-ui.filter-native-select wire:model.live="statusFilter">
                    <option value="all">{{ __('personnel::my_hr.requests.filters.all') }}</option>
                    @foreach (['pending', 'approved', 'upcoming', 'active', 'completed', 'cancelled', 'deleted'] as $status)
                        <option value="{{ $status }}">{{ __('personnel::my_hr.requests.status.'.$status) }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </x-ui.input-shell>
            <div class="grid gap-1 sm:grid-cols-2 2xl:grid-cols-2">
                <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.date_from')" labelClass="tracking-tight text-zinc-500">
                    <x-ui.filter-input wire:model.live="dateFrom" type="date" />
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.date_to')" labelClass="tracking-tight text-zinc-500">
                    <x-ui.filter-input wire:model.live="dateTo" type="date" />
                </x-ui.input-shell>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @forelse ($rows as $row)
                <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-zinc-300 hover:shadow-md">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1 space-y-3">
                            <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ $row['title'] }}</h3>

                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold tracking-tight {{ match($row['status_mode']) {
                                    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
                                    'info' => 'border-sky-200 bg-sky-50 text-sky-700',
                                    'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
                                    default => 'border-zinc-200 bg-white text-zinc-700',
                                } }}">{{ $row['status_label'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold tracking-tight text-sky-700">{{ $row['type_label'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium tracking-tight text-zinc-500">{{ $row['date_badge'] }}</span>
                            </div>
                        </div>

                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-3.5 py-1.5 text-xs font-semibold tracking-tight text-zinc-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 7h14a1 1 0 011 1v11a1 1 0 01-1 1H5a1 1 0 01-1-1V8a1 1 0 011-1z" />
                            </svg>
                            {{ $row['period'] }}
                        </span>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-zinc-500">{{ $row['summary'] }}</p>

                    <div class="mt-4 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($row['details'] as $detail)
                            <div class="rounded-2xl border border-zinc-100 bg-zinc-50/70 px-4 py-3">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">{{ $detail['label'] }}</span>
                                <p class="mt-1 text-sm font-semibold leading-6 tracking-tight text-zinc-900">{{ $detail['value'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if (($row['can_request_correction'] ?? false) === true)
                        <div class="mt-4 border-t border-zinc-200 pt-4">
                            <button type="button" wire:click="openCorrectionForm('{{ $row['request_type'] }}', {{ $row['record_id'] }})" class="inline-flex items-center justify-center rounded-2xl bg-[#f5f5f7] px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">
                                {{ __('personnel::my_hr.requests.actions.request_correction') }}
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <x-ui.empty-state icon="icons.comment-icon" :title="__('personnel::my_hr.requests.empty.title')" :message="__('personnel::my_hr.requests.empty.body')" class="py-12" />
            @endforelse
        </div>
    </div>

    @if ($showCorrectionForm)
        <div x-ref="correctionFormCard" class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 pb-4">
                <div class="space-y-1">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.review.types.correction') }}</x-ui.field-label>
                    <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.requests.actions.request_correction') }}</h3>
                </div>

                <button type="button" wire:click="cancelCorrectionForm" class="rounded-2xl bg-[#f5f5f7] px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">
                    {{ __('personnel::my_hr.requests.actions.cancel_form') }}
                </button>
            </div>

            <div class="mt-5 space-y-5">
                @if ($correctionRequestType === 'leave')
                    <div class="grid gap-3 lg:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('correctionForm.starts_at')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="correctionForm.starts_at" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('correctionForm.ends_at')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="correctionForm.ends_at" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                        </x-ui.input-shell>
                    </div>
                @elseif ($correctionRequestType === 'vacation')
                    <div class="grid gap-3 lg:grid-cols-3">
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.destination')" :error="$errors->first('correctionForm.vacation_places')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="correctionForm.vacation_places" type="text" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('correctionForm.start_date')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="correctionForm.start_date" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('correctionForm.end_date')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="correctionForm.end_date" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                        </x-ui.input-shell>
                    </div>
                @else
                    <div class="grid gap-3 lg:grid-cols-3">
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.location')" :error="$errors->first('correctionForm.location')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="correctionForm.location" type="text" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.start_date')" :error="$errors->first('correctionForm.start_date')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="correctionForm.start_date" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.end_date')" :error="$errors->first('correctionForm.end_date')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="correctionForm.end_date" type="date" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0" />
                        </x-ui.input-shell>
                    </div>

                    <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.description')" :error="$errors->first('correctionForm.description')" labelClass="tracking-tight text-zinc-500">
                        <textarea wire:model.live="correctionForm.description" rows="3" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold leading-6 text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0"></textarea>
                    </x-ui.input-shell>
                @endif

                <x-ui.input-shell :label="__('personnel::my_hr.requests.fields.reason')" :error="$errors->first('correctionForm.reason')" labelClass="tracking-tight text-zinc-500">
                    <textarea wire:model.live="correctionForm.reason" rows="4" class="w-full rounded-2xl border-0 bg-[#f5f5f7] px-4 py-3 text-sm font-semibold leading-6 text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] outline-none ring-0 transition focus:bg-white focus:outline-none focus:ring-0"></textarea>
                </x-ui.input-shell>

                <div class="flex justify-end">
                    <button type="button" wire:click="storeCorrectionRequest" class="rounded-2xl bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800">
                        {{ __('personnel::my_hr.requests.actions.submit_correction') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
