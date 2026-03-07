<div class="space-y-4">
    @unless($embedded)
        <x-surface-card :title="__('Manual Attendance Entry')" icon="icons.pending-icon">
            <p class="text-sm text-zinc-500">
                {{ __('Daily manual attendance input screen for organizations without a device system.') }}
            </p>
        </x-surface-card>
    @endunless

    @if($selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('Structure scope') }}</x-small-badge>
            <span>{{ __('Showing personnel from the selected structure tree only.') }}</span>
            <span class="font-medium">{{ $selectedStructureLabel }}</span>
        </div>
    @endif

    @if($canWrite)
        <x-surface-card :title="__('Manual entry form')">
            <div class="mb-4 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                <div class="grid gap-3 lg:grid-cols-[1.9fr_0.8fr]">
                    <div class="space-y-1">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('Input flow') }}</p>
                        <p class="text-sm text-zinc-500">{{ __('Pick personnel, set day and times, then let the system calculate metrics from shift rules or enter them manually.') }}</p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white px-3 py-3 shadow-sm">
                        <div class="flex h-full flex-col justify-between gap-3">
                            <div class="space-y-0.5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('Metric input mode') }}</p>
                                <p class="text-xs leading-5 text-zinc-500">
                                    {{ __('Controls whether attendance metrics are auto-calculated from shift rules or entered manually.') }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Current mode') }}</p>
                                    <p class="text-sm font-medium text-zinc-700">
                                        {{ $manualMetricOverride ? __('Manual override') : __('Automatic calculation') }}
                                    </p>
                                </div>

                                <x-small-badge :mode="$manualMetricOverride ? 'amber' : 'green'">
                                    {{ $manualMetricOverride ? __('Manual override') : __('Automatic calculation') }}
                                </x-small-badge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <x-ui.search-input-select
                        :label="__('Personnel')"
                        searchModel="personnelSearch"
                        :selected="$selectedPersonnel"
                        displayKey="fullname"
                        idKey="tabel_no"
                        onClear="clearPersonnel"
                        clearField="tabel_no"
                        :placeholder="__('Search personnel to create a manual entry')"
                    >
                        @forelse($personnelResults as $personnel)
                            <button
                                type="button"
                                wire:click="selectPersonnel('{{ $personnel->tabel_no }}', '{{ addslashes($personnel->fullname) }}')"
                                class="flex w-full flex-col rounded-md px-2 py-1 text-left text-slate-600 transition-all duration-300 hover:bg-white drop-shadow-sm"
                            >
                                <span>{{ $personnel->fullname }}</span>
                                <span class="text-xs font-mono text-zinc-500">{{ $personnel->tabel_no }}</span>
                                @if($personnel->structure_path)
                                    <span class="max-w-[18rem] truncate text-[11px] text-zinc-400 md:max-w-[24rem]" title="{{ $personnel->structure_path }}">
                                        {{ $personnel->structure_path }}
                                    </span>
                                @endif
                            </button>
                        @empty
                            <span class="mx-auto text-sm font-medium text-slate-500">
                                {{ __('Search personnel to create a manual entry') }}
                            </span>
                        @endforelse
                    </x-ui.search-input-select>
                    @error('form.tabel_no') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-date">{{ __('Date') }}</x-label>
                    <input
                        id="manual-form-date"
                        wire:model.live="form.date"
                        type="date"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    />
                    @error('form.date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-check-in">{{ __('Check-in time') }}</x-label>
                    <input
                        id="manual-form-check-in"
                        wire:model.live="form.check_in_at"
                        type="time"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    />
                    @error('form.check_in_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-check-out">{{ __('Check-out time') }}</x-label>
                    <input
                        id="manual-form-check-out"
                        wire:model.live="form.check_out_at"
                        type="time"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    />
                    @error('form.check_out_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-shift-source">{{ __('Shift source') }}</x-label>
                    <select
                        id="manual-form-shift-source"
                        wire:model.live="form.shift_source_mode"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    >
                        <option value="auto">{{ __('Auto (assignment/default shift)') }}</option>
                        <option value="explicit">{{ __('Selected shift') }}</option>
                    </select>
                    @error('form.shift_source_mode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-explicit-shift">{{ __('Calculation shift') }}</x-label>
                    <select
                        id="manual-form-explicit-shift"
                        wire:model.live="form.explicit_shift_id"
                        @disabled($form['shift_source_mode'] !== 'explicit')
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <option value="">{{ __('Select shift') }}</option>
                        @foreach($availableShifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                        @endforeach
                    </select>
                    @error('form.explicit_shift_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-3">
                    <div class="grid gap-3 xl:grid-cols-[1.55fr_1fr]">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-800">{{ __('Selected personnel') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('The manual entry and calculation scope is bound to this personnel record.') }}</p>
                                </div>
                            </div>

                            @if($selectedPersonnelRecord)
                                <div class="rounded-xl border border-zinc-200 bg-white p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-zinc-900">{{ $selectedPersonnelRecord->fullname }}</p>
                                            <p class="text-xs font-mono uppercase tracking-wide text-zinc-500">{{ $selectedPersonnelRecord->tabel_no }}</p>
                                            @if($selectedPersonnelRecord->structure_path)
                                                <p class="mt-1 max-w-[18rem] truncate text-xs text-zinc-500 md:max-w-[24rem]" title="{{ $selectedPersonnelRecord->structure_path }}">
                                                    {{ $selectedPersonnelRecord->structure_path }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-zinc-200 bg-white px-3 py-4 text-sm text-zinc-500">
                                    {{ __('Search and select personnel to continue with manual attendance input.') }}
                                </div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-800">{{ __('Metric mode') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('Switch between auto-calculated values and fully manual metric entry.') }}</p>
                                </div>
                            </div>

                            <label for="manual-metric-override" class="flex h-10 items-center gap-2 rounded-lg bg-white px-3 text-sm text-zinc-700 shadow-sm">
                                <input
                                    id="manual-metric-override"
                                    type="checkbox"
                                    wire:model.live="manualMetricOverride"
                                    class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span>{{ __('Manual override') }}</span>
                            </label>

                            <p class="mt-3 text-xs text-zinc-500">
                                {{ __('If check-in and check-out are entered, worked/late/early values are calculated automatically using the assigned shift or global default shift.') }}
                            </p>
                            @if(!$manualMetricOverride)
                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ __('To enter these metrics manually, enable manual override.') }}
                                </p>
                            @endif
                            @if($autoCalculatedPreview)
                                <p class="mt-1 text-xs font-medium text-emerald-600">
                                    {{ __('Worked, overtime, late and early leave values were auto-filled from the entered times.') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <div class="grid gap-3 xl:grid-cols-[1.55fr_1fr]">
                        <div class="space-y-3">
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-800">{{ __('Shift baseline') }}</p>
                                        <p class="text-xs text-zinc-500">{{ __('Review which shift baseline is used before saving the manual entry.') }}</p>
                                    </div>
                                </div>

                                @if($form['shift_source_mode'] === 'auto')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-zinc-900">{{ __('Auto calculation baseline') }}</p>
                                                <p class="text-xs text-zinc-500">{{ __('The system first checks personnel assignment, then falls back to the global default shift.') }}</p>
                                            </div>

                                            @if($baselineContext['baseline_label'])
                                                <div class="flex flex-col items-start gap-1">
                                                    <x-small-badge mode="blue">{{ __('Detected source') }}: {{ __($baselineContext['baseline_source']) }}</x-small-badge>
                                                    <x-small-badge mode="sky">{{ $baselineContext['baseline_label'] }}</x-small-badge>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-sm font-semibold text-zinc-800">{{ __('Assigned shift') }}</p>
                                                    @if($selectedPersonnelActiveAssignment?->shift)
                                                        <x-small-badge mode="green">{{ __('Active assignment') }}</x-small-badge>
                                                    @endif
                                                </div>

                                                @if($selectedPersonnelActiveAssignment?->shift)
                                                    <div class="mt-2 flex flex-col gap-1 text-xs text-zinc-500">
                                                        <x-small-badge mode="sky">{{ $selectedPersonnelActiveAssignment->shift->name }}</x-small-badge>
                                                        <span>
                                                            {{ $selectedPersonnelActiveAssignment->shift->start_time }} - {{ $selectedPersonnelActiveAssignment->shift->end_time }}
                                                            • {{ __('Break') }}: {{ $selectedPersonnelActiveAssignment->shift->break_minutes }} {{ __('min') }}
                                                        </span>
                                                        <span>{{ __('This personnel-specific shift will be used for automatic late/early/overtime calculation.') }}</span>
                                                    </div>
                                                @else
                                                    <p class="mt-2 text-xs text-zinc-500">{{ __('No active shift assignment') }}</p>
                                                @endif
                                            </div>

                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-sm font-semibold text-zinc-800">{{ __('Default shift fallback') }}</p>
                                                    @if($currentDefaultShift)
                                                        <x-small-badge mode="blue">{{ __('Configured') }}</x-small-badge>
                                                    @endif
                                                </div>

                                                @if($currentDefaultShift)
                                                    <div class="mt-2 flex flex-col gap-1 text-xs text-zinc-500">
                                                        <x-small-badge mode="sky">{{ $currentDefaultShift->name }}</x-small-badge>
                                                        <span>
                                                            {{ $currentDefaultShift->start_time }} - {{ $currentDefaultShift->end_time }}
                                                            • {{ __('Break') }}: {{ $currentDefaultShift->break_minutes }} {{ __('min') }}
                                                        </span>
                                                        <span>{{ __('This shift is used when personnel do not have an active assignment.') }}</span>
                                                    </div>
                                                @else
                                                    <p class="mt-2 text-xs text-zinc-500">{{ __('No default shift configured') }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        @if(! $baselineContext['baseline_label'])
                                            <div class="mt-3 flex items-start gap-2 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                                <x-small-badge mode="red">{{ __('No baseline shift available') }}</x-small-badge>
                                                <span>{{ __('To calculate planned/late/early/overtime values, either assign a shift to the personnel, choose Selected shift, or configure a default shift in Settings.') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-3">
                                        @if($selectedShiftPreview)
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-blue-900">{{ __('Selected shift for calculation') }}</p>
                                                    <p class="text-xs text-blue-700">{{ __('This selected shift is used instead of assignment/default shift.') }}</p>
                                                </div>
                                                <div class="flex flex-col items-start gap-1">
                                                    <x-small-badge mode="sky">{{ $selectedShiftPreview->name }}</x-small-badge>
                                                    <span class="text-xs text-blue-700">
                                                        {{ $selectedShiftPreview->start_time }} - {{ $selectedShiftPreview->end_time }}
                                                        • {{ __('Break') }}: {{ $selectedShiftPreview->break_minutes }} {{ __('min') }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2 text-sm text-amber-700">
                                                <x-small-badge>{{ __('Shift required') }}</x-small-badge>
                                                <span>{{ __('Select a shift to calculate planned/late/early/overtime values.') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-800">{{ __('Calculated metrics') }}</p>
                                        <p class="text-xs text-zinc-500">{{ __('Use automatic calculation or manually override attendance metrics before approval.') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <x-label for="manual-form-worked">{{ __('Worked minutes') }}</x-label>
                                        <x-livewire-input id="manual-form-worked" mode="gray" type="number" min="0" name="form.worked_minutes" wire:model.defer="form.worked_minutes" :readonly="!$manualMetricOverride" />
                                        @error('form.worked_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label for="manual-form-overtime">{{ __('Overtime minutes') }}</x-label>
                                        <x-livewire-input id="manual-form-overtime" mode="gray" type="number" min="0" name="form.overtime_minutes" wire:model.defer="form.overtime_minutes" :readonly="!$manualMetricOverride" />
                                        @error('form.overtime_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label for="manual-form-late">{{ __('Late minutes') }}</x-label>
                                        <x-livewire-input id="manual-form-late" mode="gray" type="number" min="0" name="form.late_minutes" wire:model.defer="form.late_minutes" :readonly="!$manualMetricOverride" />
                                        @error('form.late_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label for="manual-form-early-leave">{{ __('Early leave minutes') }}</x-label>
                                        <x-livewire-input id="manual-form-early-leave" mode="gray" type="number" min="0" name="form.early_leave_minutes" wire:model.defer="form.early_leave_minutes" :readonly="!$manualMetricOverride" />
                                        @error('form.early_leave_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label for="manual-form-absence">{{ __('Absence code') }}</x-label>
                                        <x-livewire-input id="manual-form-absence" mode="gray" name="form.absence_code" wire:model.defer="form.absence_code" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <x-label for="manual-form-status">{{ __('Approval') }}</x-label>
                    <div id="manual-form-status" class="rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-3">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-0.5">
                                <p class="text-sm font-medium text-zinc-800">{{ __('Manual entry always starts as') }}</p>
                                <p class="text-xs text-zinc-500">{{ __('Saved entries go to approval queue before being posted to attendance ledgers.') }}</p>
                            </div>

                            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-700 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                {{ __('pending') }}
                            </span>
                        </div>
                    </div>
                </div>
                @if($autoCalculatedPreview || $manualMetricOverride)
                    <div class="md:col-span-3">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-zinc-800">{{ __('Live calculation summary') }}</p>
                                <p class="text-xs text-zinc-500">
                                    @if($preview['baseline_source'] === 'explicit_shift')
                                        {{ __('Calculated with selected shift: :shift', ['shift' => $preview['baseline_label']]) }}
                                    @elseif($preview['baseline_source'] === 'assignment_shift')
                                        {{ __('Calculated with assigned shift: :shift', ['shift' => $preview['baseline_label']]) }}
                                    @elseif($preview['baseline_source'] === 'default_shift')
                                        {{ __('Calculated with default shift: :shift', ['shift' => $preview['baseline_label']]) }}
                                    @elseif($preview['baseline_source'] === 'manual_override')
                                        {{ __('Manual override is active.') }}
                                    @else
                                        {{ __('No shift baseline found. Worked minutes are calculated from entered times, but late/early/overtime remain 0 until a shift is assigned or a default shift is configured.') }}
                                    @endif
                                </p>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-zinc-500">
                                <span class="rounded-full bg-white px-2 py-1 shadow-sm">{{ __('Source') }}: {{ __($preview['baseline_source']) }}</span>
                                @if($preview['baseline_label'])
                                    <span class="rounded-full bg-white px-2 py-1 shadow-sm">{{ __('Shift') }}: {{ $preview['baseline_label'] }}</span>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-5">
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('Planned') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-zinc-900">{{ $preview['planned_minutes'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('Worked') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-zinc-900">{{ $preview['worked_minutes'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('Late minutes') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-amber-600">{{ $preview['late_minutes'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('Early leave minutes') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-rose-600">{{ $preview['early_leave_minutes'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('Overtime minutes') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-emerald-600">{{ $preview['overtime_minutes'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="md:col-span-3">
                    <x-label for="manual-form-reason">{{ __('Reason') }}</x-label>
                    <textarea
                        id="manual-form-reason"
                        wire:model.defer="form.reason"
                        rows="3"
                        class="w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"
                    ></textarea>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-zinc-800">{{ __('Submit manual entry') }}</p>
                    <p class="text-xs text-zinc-500">{{ __('Saved entries go to approval queue before being posted to attendance ledgers.') }}</p>
                </div>
                <x-button mode="primary" wire:click="save">{{ __('Save') }}</x-button>
            </div>
        </x-surface-card>
    @endif

    <x-surface-card :title="__('Manual override queue')">
        <div class="mb-3 space-y-3">
            <div class="space-y-1">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('Approval queue') }}</p>
                <p class="text-sm text-zinc-500">{{ __('Review submitted manual attendance entries, approve them or return them with a reject note.') }}</p>
            </div>

            <div class="w-full sm:w-48">
                <x-label for="manual-queue-status">{{ __('Status filter') }}</x-label>
                <select
                    id="manual-queue-status"
                    wire:model.live="queueStatus"
                    class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                >
                    <option value="pending">{{ __('pending') }}</option>
                    <option value="approved">{{ __('approved') }}</option>
                    <option value="rejected">{{ __('rejected') }}</option>
                    <option value="all">{{ __('all') }}</option>
                </select>
            </div>
        </div>

        <div class="relative overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[
                        __('#'),
                        __('Personnel'),
                        __('Date'),
                        __('Check-in'),
                        __('Check-out'),
                        __('Worked'),
                        __('Overtime'),
                        __('Late (min)'),
                        __('Early (min)'),
                        __('Status'),
                        __('Entered by'),
                        __('Approved by'),
                        __('Actions')
                    ]">
                        @forelse($recentEntries as $entry)
                            @php
                                $statusClass = match($entry->approval_status) {
                                    'approved' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                        <tr>
                            <x-table.td>{{ $entry->id }}</x-table.td>
                            <x-table.td extraClasses="text-zinc-700">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900">
                                        {{ $entry->personnel?->fullname ?? $entry->tabel_no }}
                                    </span>
                                    <span class="text-xs font-mono uppercase text-zinc-500">{{ $entry->tabel_no }}</span>
                                    @if($entry->personnel?->structure_path)
                                        <span class="max-w-[18rem] truncate text-xs text-zinc-500 md:max-w-[24rem]" title="{{ $entry->personnel->structure_path }}">
                                            {{ $entry->personnel->structure_path }}
                                        </span>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td>{{ optional($entry->date)->format('Y-m-d') }}</x-table.td>
                                <x-table.td>{{ $entry->check_in_at ?: '-' }}</x-table.td>
                                <x-table.td>{{ $entry->check_out_at ?: '-' }}</x-table.td>
                                <x-table.td>{{ $entry->worked_minutes }}</x-table.td>
                                <x-table.td>{{ $entry->overtime_minutes }}</x-table.td>
                                <x-table.td>{{ $entry->late_minutes }}</x-table.td>
                                <x-table.td>{{ $entry->early_leave_minutes }}</x-table.td>
                                <x-table.td>
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs uppercase font-medium {{ $statusClass }}">
                                        {{ __($entry->approval_status) }}
                                    </span>
                                </x-table.td>
                                <x-table.td extraClasses="text-zinc-600">{{ $entry->enteredBy?->name ?? '-' }}</x-table.td>
                                <x-table.td extraClasses="text-zinc-600">{{ $entry->approvedBy?->name ?? '-' }}</x-table.td>
                                <x-table.td :isButton="true">
                                    @if($canApprove && $entry->approval_status === 'pending')
                                        <div class="inline-flex items-center gap-2">
                                            <input
                                                wire:model.defer="rejectNotes.{{ $entry->id }}"
                                                type="text"
                                                placeholder="{{ __('Reject note') }}"
                                                class="h-8 w-36 rounded-md border border-zinc-200 bg-zinc-100 px-2 text-xs"
                                            />
                                            <x-button mode="approve" class="!h-8 !px-3 !text-xs uppercase" wire:click="approve({{ $entry->id }})">
                                                {{ __('Approve') }}
                                            </x-button>
                                            <x-button mode="reject" class="!h-8 !px-3 !text-xs uppercase" wire:click="reject({{ $entry->id }})">
                                                {{ __('Reject') }}
                                            </x-button>
                                        </div>
                                    @else
                                        <span class="text-xs text-zinc-500">-</span>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty :rows="9" />
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>

        <div class="mt-3">
            {{ $recentEntries->links() }}
        </div>
    </x-surface-card>
</div>
