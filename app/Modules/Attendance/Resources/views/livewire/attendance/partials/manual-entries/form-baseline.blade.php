{{-- Shift baseline the metrics are calculated from, next to the calculated metric inputs. --}}
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
                        <x-livewire-input id="manual-form-worked" mode="gray" type="number" min="0" name="form.worked_minutes" wire:model="form.worked_minutes" :readonly="!$manualMetricOverride" />
                        @error('form.worked_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="manual-form-overtime">{{ __('attendance::manual_entries.labels.overtime_minutes') }}</x-label>
                        <x-livewire-input id="manual-form-overtime" mode="gray" type="number" min="0" name="form.overtime_minutes" wire:model="form.overtime_minutes" :readonly="!$manualMetricOverride" />
                        @error('form.overtime_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="manual-form-late">{{ __('attendance::manual_entries.labels.late_minutes') }}</x-label>
                        <x-livewire-input id="manual-form-late" mode="gray" type="number" min="0" name="form.late_minutes" wire:model="form.late_minutes" :readonly="!$manualMetricOverride" />
                        @error('form.late_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="manual-form-early-leave">{{ __('attendance::manual_entries.labels.early_leave_minutes') }}</x-label>
                        <x-livewire-input id="manual-form-early-leave" mode="gray" type="number" min="0" name="form.early_leave_minutes" wire:model="form.early_leave_minutes" :readonly="!$manualMetricOverride" />
                        @error('form.early_leave_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="manual-form-absence">{{ __('attendance::manual_entries.labels.absence_code') }}</x-label>
                        <x-livewire-input id="manual-form-absence" mode="gray" name="form.absence_code" wire:model="form.absence_code" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
