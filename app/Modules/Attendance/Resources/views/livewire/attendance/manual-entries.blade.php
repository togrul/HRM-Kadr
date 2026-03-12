@php
    $baselineSourceLabels = [
        'none' => __('attendance::manual_entries.sources.none'),
        'manual_override' => __('attendance::manual_entries.sources.manual_override'),
        'explicit_shift' => __('attendance::manual_entries.sources.explicit_shift'),
        'assignment_shift' => __('attendance::manual_entries.sources.assignment_shift'),
        'default_shift' => __('attendance::manual_entries.sources.default_shift'),
    ];
@endphp

<div class="space-y-4">
    @unless($embedded)
        <x-surface-card :title="__('attendance::manual_entries.titles.page')" icon="icons.pending-icon">
            <p class="text-sm text-zinc-500">
                {{ __('attendance::manual_entries.descriptions.page') }}
            </p>
        </x-surface-card>
    @endunless

    @island(name: 'attendance-manual-workbench')
    @if($this->selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('attendance::manual_entries.labels.structure_scope') }}</x-small-badge>
            <span>{{ __('attendance::manual_entries.descriptions.scope') }}</span>
            <span class="font-medium">{{ $this->selectedStructureLabel }}</span>
        </div>
    @endif
    @endisland

    @island(name: 'attendance-manual-workbench')
    @if($canWrite)
        <x-surface-card :title="__('attendance::manual_entries.titles.form')">
            <div class="mb-4 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                <div class="grid gap-3 lg:grid-cols-[1.9fr_0.8fr]">
                    <div class="space-y-1">
                        <p class="text-[11px] font-semibold uppercase  text-zinc-400">{{ __('attendance::manual_entries.labels.input_flow') }}</p>
                        <p class="text-sm text-zinc-500">{{ __('attendance::manual_entries.descriptions.input_flow') }}</p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white px-3 py-3 shadow-sm">
                        <div class="flex h-full flex-col justify-between gap-3">
                            <div class="space-y-0.5">
                                <p class="text-[11px] font-semibold uppercase  text-zinc-400">{{ __('attendance::manual_entries.labels.metric_input_mode') }}</p>
                                <p class="text-xs leading-5 text-zinc-500">
                                    {{ __('attendance::manual_entries.descriptions.metric_mode') }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('attendance::manual_entries.labels.current_mode') }}</p>
                                    <p class="text-sm font-medium text-zinc-700">
                                        {{ $manualMetricOverride ? __('attendance::manual_entries.modes.manual_override') : __('attendance::manual_entries.modes.automatic_calculation') }}
                                    </p>
                                </div>

                                <x-small-badge :mode="$manualMetricOverride ? 'amber' : 'green'">
                                    {{ $manualMetricOverride ? __('attendance::manual_entries.modes.manual_override') : __('attendance::manual_entries.modes.automatic_calculation') }}
                                </x-small-badge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <x-ui.search-input-select
                        :label="__('attendance::manual_entries.labels.personnel')"
                        searchModel="personnelSearch"
                        :selected="$selectedPersonnel"
                        displayKey="fullname"
                        idKey="tabel_no"
                        onClear="clearPersonnel"
                        clearField="tabel_no"
                        :placeholder="__('attendance::manual_entries.placeholders.search_personnel')"
                    >
                        @forelse($this->personnelResults as $personnel)
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
                                {{ __('attendance::manual_entries.placeholders.search_personnel') }}
                            </span>
                        @endforelse
                    </x-ui.search-input-select>
                    @error('form.tabel_no') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-date">{{ __('attendance::manual_entries.labels.date') }}</x-label>
                    <input
                        id="manual-form-date"
                        wire:model.live="form.date"
                        type="date"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    />
                    @error('form.date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-check-in">{{ __('attendance::manual_entries.labels.check_in_time') }}</x-label>
                    <input
                        id="manual-form-check-in"
                        wire:model.live="form.check_in_at"
                        type="time"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    />
                    @error('form.check_in_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-check-out">{{ __('attendance::manual_entries.labels.check_out_time') }}</x-label>
                    <input
                        id="manual-form-check-out"
                        wire:model.live="form.check_out_at"
                        type="time"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    />
                    @error('form.check_out_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-shift-source">{{ __('attendance::manual_entries.labels.shift_source') }}</x-label>
                    <select
                        id="manual-form-shift-source"
                        wire:model.live="form.shift_source_mode"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    >
                        <option value="auto">{{ __('attendance::manual_entries.options.shift_source_auto') }}</option>
                        <option value="explicit">{{ __('attendance::manual_entries.options.shift_source_explicit') }}</option>
                    </select>
                    @error('form.shift_source_mode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="manual-form-explicit-shift">{{ __('attendance::manual_entries.labels.calculation_shift') }}</x-label>
                    <select
                        id="manual-form-explicit-shift"
                        wire:model.live="form.explicit_shift_id"
                        @disabled($form['shift_source_mode'] !== 'explicit')
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <option value="">{{ __('attendance::manual_entries.options.select_shift') }}</option>
                        @foreach($this->availableShifts as $shift)
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
                                    <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.selected_personnel') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.selected_personnel') }}</p>
                                </div>
                            </div>

                            @if($this->selectedPersonnelRecord)
                                <div class="rounded-xl border border-zinc-200 bg-white p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-zinc-900">{{ $this->selectedPersonnelRecord->fullname }}</p>
                                            <p class="text-xs font-mono uppercase tracking-wide text-zinc-500">{{ $this->selectedPersonnelRecord->tabel_no }}</p>
                                            @if($this->selectedPersonnelRecord->structure_path)
                                                <p class="mt-1 max-w-[18rem] truncate text-xs text-zinc-500 md:max-w-[24rem]" title="{{ $this->selectedPersonnelRecord->structure_path }}">
                                                    {{ $this->selectedPersonnelRecord->structure_path }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-zinc-200 bg-white px-3 py-4 text-sm text-zinc-500">
                                    {{ __('attendance::manual_entries.descriptions.search_and_select_personnel') }}
                                </div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.metric_mode') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.metric_switch') }}</p>
                                </div>
                            </div>

                            <label for="manual-metric-override" class="flex h-10 items-center gap-2 rounded-lg bg-white px-3 text-sm text-zinc-700 shadow-sm">
                                <input
                                    id="manual-metric-override"
                                    type="checkbox"
                                    wire:model.live="manualMetricOverride"
                                    class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span>{{ __('attendance::manual_entries.modes.manual_override') }}</span>
                            </label>

                            <p class="mt-3 text-xs text-zinc-500">
                                {{ __('attendance::manual_entries.descriptions.metric_auto_fill') }}
                            </p>
                            @if(!$manualMetricOverride)
                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ __('attendance::manual_entries.descriptions.enable_manual_override') }}
                                </p>
                            @endif
                            @if($autoCalculatedPreview)
                                <p class="mt-1 text-xs font-medium text-emerald-600">
                                    {{ __('attendance::manual_entries.descriptions.auto_filled') }}
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
                                        <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.shift_baseline') }}</p>
                                        <p class="text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.shift_baseline') }}</p>
                                    </div>
                                </div>

                                @if($form['shift_source_mode'] === 'auto')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-zinc-900">{{ __('attendance::manual_entries.labels.auto_calculation_baseline') }}</p>
                                                <p class="text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.auto_baseline') }}</p>
                                            </div>

                                            @if($this->baselineContext['baseline_label'])
                                                <div class="flex flex-col items-start gap-1">
                                                    <x-small-badge mode="blue">{{ __('attendance::manual_entries.labels.detected_source') }}: {{ $baselineSourceLabels[$this->baselineContext['baseline_source']] ?? $this->baselineContext['baseline_source'] }}</x-small-badge>
                                                    <x-small-badge mode="sky">{{ $this->baselineContext['baseline_label'] }}</x-small-badge>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.assigned_shift') }}</p>
                                                    @if($this->selectedPersonnelActiveAssignment?->shift)
                                                        <x-small-badge mode="green">{{ __('attendance::manual_entries.labels.active_assignment') }}</x-small-badge>
                                                    @endif
                                                </div>

                                                @if($this->selectedPersonnelActiveAssignment?->shift)
                                                    <div class="mt-2 flex flex-col gap-1 text-xs text-zinc-500">
                                                        <x-small-badge mode="sky">{{ $this->selectedPersonnelActiveAssignment->shift->name }}</x-small-badge>
                                                        <span>
                                                            {{ $this->selectedPersonnelActiveAssignment->shift->start_time }} - {{ $this->selectedPersonnelActiveAssignment->shift->end_time }}
                                                            • {{ __('attendance::manual_entries.labels.break') }}: {{ $this->selectedPersonnelActiveAssignment->shift->break_minutes }} {{ __('attendance::manual_entries.labels.min') }}
                                                        </span>
                                                        <span>{{ __('attendance::manual_entries.descriptions.assignment_shift') }}</span>
                                                    </div>
                                                @else
                                                    <p class="mt-2 text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.no_active_assignment') }}</p>
                                                @endif
                                            </div>

                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.default_shift_fallback') }}</p>
                                                    @if($this->currentDefaultShift)
                                                        <x-small-badge mode="blue">{{ __('attendance::manual_entries.labels.configured') }}</x-small-badge>
                                                    @endif
                                                </div>

                                                @if($this->currentDefaultShift)
                                                    <div class="mt-2 flex flex-col gap-1 text-xs text-zinc-500">
                                                        <x-small-badge mode="sky">{{ $this->currentDefaultShift->name }}</x-small-badge>
                                                        <span>
                                                            {{ $this->currentDefaultShift->start_time }} - {{ $this->currentDefaultShift->end_time }}
                                                            • {{ __('attendance::manual_entries.labels.break') }}: {{ $this->currentDefaultShift->break_minutes }} {{ __('attendance::manual_entries.labels.min') }}
                                                        </span>
                                                        <span>{{ __('attendance::manual_entries.descriptions.default_shift_usage') }}</span>
                                                    </div>
                                                @else
                                                    <p class="mt-2 text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.no_default_shift') }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        @if(! $this->baselineContext['baseline_label'])
                                            <div class="mt-3 flex items-start gap-2 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                                <x-small-badge mode="red">{{ __('attendance::manual_entries.labels.shift_required') }}</x-small-badge>
                                                <span>{{ __('attendance::manual_entries.descriptions.no_baseline') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-3">
                                        @if($this->selectedShiftPreview)
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-blue-900">{{ __('attendance::manual_entries.labels.selected_shift_for_calculation') }}</p>
                                                    <p class="text-xs text-blue-700">{{ __('attendance::manual_entries.descriptions.selected_shift') }}</p>
                                                </div>
                                                <div class="flex flex-col items-start gap-1">
                                                    <x-small-badge mode="sky">{{ $this->selectedShiftPreview->name }}</x-small-badge>
                                                    <span class="text-xs text-blue-700">
                                                        {{ $this->selectedShiftPreview->start_time }} - {{ $this->selectedShiftPreview->end_time }}
                                                        • {{ __('attendance::manual_entries.labels.break') }}: {{ $this->selectedShiftPreview->break_minutes }} {{ __('attendance::manual_entries.labels.min') }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2 text-sm text-amber-700">
                                                <x-small-badge>{{ __('attendance::manual_entries.labels.shift_required') }}</x-small-badge>
                                                <span>{{ __('attendance::manual_entries.descriptions.select_shift') }}</span>
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
                                        <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.calculated_metrics') }}</p>
                                        <p class="text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.calculated_metrics') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <x-label for="manual-form-worked">{{ __('attendance::manual_entries.labels.worked_minutes') }}</x-label>
                                        <x-livewire-input id="manual-form-worked" mode="gray" type="number" min="0" name="form.worked_minutes" wire:model.defer="form.worked_minutes" :readonly="!$manualMetricOverride" />
                                        @error('form.worked_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label for="manual-form-overtime">{{ __('attendance::manual_entries.labels.overtime_minutes') }}</x-label>
                                        <x-livewire-input id="manual-form-overtime" mode="gray" type="number" min="0" name="form.overtime_minutes" wire:model.defer="form.overtime_minutes" :readonly="!$manualMetricOverride" />
                                        @error('form.overtime_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label for="manual-form-late">{{ __('attendance::manual_entries.labels.late_minutes') }}</x-label>
                                        <x-livewire-input id="manual-form-late" mode="gray" type="number" min="0" name="form.late_minutes" wire:model.defer="form.late_minutes" :readonly="!$manualMetricOverride" />
                                        @error('form.late_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label for="manual-form-early-leave">{{ __('attendance::manual_entries.labels.early_leave_minutes') }}</x-label>
                                        <x-livewire-input id="manual-form-early-leave" mode="gray" type="number" min="0" name="form.early_leave_minutes" wire:model.defer="form.early_leave_minutes" :readonly="!$manualMetricOverride" />
                                        @error('form.early_leave_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-label for="manual-form-absence">{{ __('attendance::manual_entries.labels.absence_code') }}</x-label>
                                        <x-livewire-input id="manual-form-absence" mode="gray" name="form.absence_code" wire:model.defer="form.absence_code" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <x-label for="manual-form-status">{{ __('attendance::manual_entries.labels.approval') }}</x-label>
                    <div id="manual-form-status" class="rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-3">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-0.5">
                                <p class="text-sm font-medium text-zinc-800">{{ __('attendance::manual_entries.labels.approval_queue') }}</p>
                                <p class="text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.approval_state') }}</p>
                            </div>

                            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-700 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                {{ __('attendance::manual_entries.statuses.pending') }}
                            </span>
                        </div>
                    </div>
                </div>
                @if($autoCalculatedPreview || $manualMetricOverride)
                    <div class="md:col-span-3">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.live_calculation_summary') }}</p>
                                <p class="text-xs text-zinc-500">
                                    @if($preview['baseline_source'] === 'explicit_shift')
                                        {{ __('attendance::manual_entries.summaries.calculated_with_selected_shift', ['shift' => $preview['baseline_label']]) }}
                                    @elseif($preview['baseline_source'] === 'assignment_shift')
                                        {{ __('attendance::manual_entries.summaries.calculated_with_assigned_shift', ['shift' => $preview['baseline_label']]) }}
                                    @elseif($preview['baseline_source'] === 'default_shift')
                                        {{ __('attendance::manual_entries.summaries.calculated_with_default_shift', ['shift' => $preview['baseline_label']]) }}
                                    @elseif($preview['baseline_source'] === 'manual_override')
                                        {{ __('attendance::manual_entries.descriptions.manual_override_active') }}
                                    @else
                                        {{ __('attendance::manual_entries.descriptions.no_shift_baseline') }}
                                    @endif
                                </p>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-zinc-500">
                                <span class="rounded-full bg-white px-2 py-1 shadow-sm">{{ __('attendance::manual_entries.labels.source') }}: {{ $baselineSourceLabels[$preview['baseline_source']] ?? $preview['baseline_source'] }}</span>
                                @if($preview['baseline_label'])
                                    <span class="rounded-full bg-white px-2 py-1 shadow-sm">{{ __('attendance::manual_entries.labels.shift') }}: {{ $preview['baseline_label'] }}</span>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-5">
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.planned') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-zinc-900">{{ $preview['planned_minutes'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.worked') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-zinc-900">{{ $preview['worked_minutes'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.late_minutes') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-amber-600">{{ $preview['late_minutes'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.early_leave_minutes') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-rose-600">{{ $preview['early_leave_minutes'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.overtime_minutes') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-emerald-600">{{ $preview['overtime_minutes'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="md:col-span-3">
                    <x-label for="manual-form-reason">{{ __('attendance::manual_entries.labels.reason') }}</x-label>
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
                    <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.submit_manual_entry') }}</p>
                    <p class="text-xs text-zinc-500">{{ __('attendance::manual_entries.descriptions.approval_state') }}</p>
                </div>
                <x-button mode="primary" wire:click="save">{{ __('attendance::manual_entries.actions.save') }}</x-button>
            </div>
        </x-surface-card>
    @endif
    @endisland

    @island(name: 'attendance-manual-queue')
    <x-surface-card :title="__('attendance::manual_entries.titles.queue')">
        <div class="mb-3 space-y-3">
            <div class="space-y-1">
                <p class="text-[11px] font-semibold uppercase  text-zinc-400">{{ __('attendance::manual_entries.labels.approval_queue') }}</p>
                <p class="text-sm text-zinc-500">{{ __('attendance::manual_entries.descriptions.queue') }}</p>
            </div>

            <div class="w-full sm:w-48">
                <x-label for="manual-queue-status">{{ __('attendance::manual_entries.labels.status_filter') }}</x-label>
                <select
                    id="manual-queue-status"
                    wire:model.live="queueStatus"
                    class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                >
                    <option value="pending">{{ __('attendance::manual_entries.statuses.pending') }}</option>
                    <option value="approved">{{ __('attendance::manual_entries.statuses.approved') }}</option>
                    <option value="rejected">{{ __('attendance::manual_entries.statuses.rejected') }}</option>
                    <option value="all">{{ __('attendance::manual_entries.statuses.all') }}</option>
                </select>
            </div>
        </div>

        <div class="relative overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[
                        __('personnel::common.labels.number'),
                        __('attendance::manual_entries.labels.personnel'),
                        __('attendance::manual_entries.labels.date'),
                        __('attendance::manual_entries.labels.check_in'),
                        __('attendance::manual_entries.labels.check_out'),
                        __('attendance::manual_entries.labels.worked'),
                        __('attendance::manual_entries.labels.overtime'),
                        __('attendance::manual_entries.labels.late_minutes'),
                        __('attendance::manual_entries.labels.early_leave_minutes'),
                        __('attendance::manual_entries.labels.status'),
                        __('attendance::manual_entries.labels.entered_by'),
                        __('attendance::manual_entries.labels.approved_by'),
                        __('attendance::manual_entries.labels.actions')
                    ]">
                        @forelse($this->recentEntries as $entry)
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
                                        {{ $this->approvalStatusLabel((string) $entry->approval_status) }}
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
                                                placeholder="{{ __('attendance::manual_entries.placeholders.reject_note') }}"
                                                class="h-8 w-36 rounded-md border border-zinc-200 bg-zinc-100 px-2 text-xs"
                                            />
                                            <x-button mode="approve" class="!h-8 !px-3 !text-xs uppercase" wire:click="approve({{ $entry->id }})">
                                                {{ __('attendance::manual_entries.actions.approve') }}
                                            </x-button>
                                            <x-button mode="reject" class="!h-8 !px-3 !text-xs uppercase" wire:click="reject({{ $entry->id }})">
                                                {{ __('attendance::manual_entries.actions.reject') }}
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
            {{ $this->recentEntries->links() }}
        </div>
    </x-surface-card>
    @endisland
</div>
