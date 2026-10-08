{{-- Manual entry form: who/when, shift source, metrics, reason. Included inside the workbench island, so everything it reads is a component property or computed. --}}
@php
    $baselineSourceLabels = [
        'none' => __('attendance::manual_entries.sources.none'),
        'manual_override' => __('attendance::manual_entries.sources.manual_override'),
        'explicit_shift' => __('attendance::manual_entries.sources.explicit_shift'),
        'assignment_shift' => __('attendance::manual_entries.sources.assignment_shift'),
        'default_shift' => __('attendance::manual_entries.sources.default_shift'),
    ];
@endphp

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
                            <p class="hrm-eyebrow">{{ __('attendance::manual_entries.labels.current_mode') }}</p>
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
                        class="flex w-full flex-col rounded-md px-2 py-1 text-left text-zinc-600 transition-all duration-300 hover:bg-white drop-shadow-sm"
                    >
                        <span>{{ $personnel->fullname }}</span>
                        <span class="text-xs font-mono text-zinc-500">{{ $personnel->tabel_no }}</span>
                        @if($personnel->structure_path)
                            <span class="max-w-[18rem] truncate text-[11px] text-zinc-400 md:max-w-[24rem]" title="{{ $personnel->structure_path }}">
                                {{ $personnel->structure_name }}
                            </span>
                        @endif
                    </button>
                @empty
                    <span class="mx-auto text-sm font-medium text-zinc-500">
                        {{ __('attendance::manual_entries.placeholders.search_personnel') }}
                    </span>
                @endforelse
            </x-ui.search-input-select>
            @error('form.tabel_no') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div>
            <x-label for="manual-form-date">{{ __('attendance::manual_entries.labels.date') }}</x-label>
            <x-ui.date-input
                id="manual-form-date"
                wire:model.live="form.date"
            />
            @error('form.date') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div>
            <x-label for="manual-form-check-in">{{ __('attendance::manual_entries.labels.check_in_time') }}</x-label>
            <input
                id="manual-form-check-in"
                wire:model.live="form.check_in_at"
                type="time"
                class="w-full min-w-0 rounded-[10px] border border-hairline bg-[#f4f4f5] text-ink shadow-sm outline-none transition-colors placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-[3px] focus:ring-[#e4e4e7] disabled:cursor-not-allowed disabled:opacity-50 h-10 px-3 text-base sm:text-sm"
            />
            @error('form.check_in_at') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div>
            <x-label for="manual-form-check-out">{{ __('attendance::manual_entries.labels.check_out_time') }}</x-label>
            <input
                id="manual-form-check-out"
                wire:model.live="form.check_out_at"
                type="time"
                class="w-full min-w-0 rounded-[10px] border border-hairline bg-[#f4f4f5] text-ink shadow-sm outline-none transition-colors placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-[3px] focus:ring-[#e4e4e7] disabled:cursor-not-allowed disabled:opacity-50 h-10 px-3 text-base sm:text-sm"
            />
            @error('form.check_out_at') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div>
            <x-label for="manual-form-shift-source">{{ __('attendance::manual_entries.labels.shift_source') }}</x-label>
            <x-ui.select
                id="manual-form-shift-source"
                wire:model.live="form.shift_source_mode"
            >
                <option value="auto">{{ __('attendance::manual_entries.options.shift_source_auto') }}</option>
                <option value="explicit">{{ __('attendance::manual_entries.options.shift_source_explicit') }}</option>
            </x-ui.select>
            @error('form.shift_source_mode') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div>
            <x-label for="manual-form-explicit-shift">{{ __('attendance::manual_entries.labels.calculation_shift') }}</x-label>
            <x-ui.select
                id="manual-form-explicit-shift"
                wire:model.live="form.explicit_shift_id"
                :disabled="$form['shift_source_mode'] !== 'explicit'"
            >
                <option value="">{{ __('attendance::manual_entries.options.select_shift') }}</option>
                @foreach($this->availableShifts as $shift)
                    <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                @endforeach
            </x-ui.select>
            @error('form.explicit_shift_id') <x-validation>{{ $message }}</x-validation> @enderror
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
                                            {{ $this->selectedPersonnelRecord->structure_name }}
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
                            class="h-4 w-4 rounded border-hairline text-ink focus:ring-zinc-400"
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

        @include('attendance::livewire.attendance.partials.manual-entries.form-baseline')

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

        @include('attendance::livewire.attendance.partials.manual-entries.form-live-summary')

        <div class="md:col-span-3">
            <x-label for="manual-form-reason">{{ __('attendance::manual_entries.labels.reason') }}</x-label>
            <textarea
                id="manual-form-reason"
                wire:model="form.reason"
                rows="3"
                class="w-full min-w-0 rounded-[10px] border border-hairline bg-[#f4f4f5] text-ink shadow-sm outline-none transition-colors placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-[3px] focus:ring-[#e4e4e7] disabled:cursor-not-allowed disabled:opacity-50 px-3 py-2.5 text-base leading-relaxed sm:text-sm"
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
