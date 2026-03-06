<div class="space-y-4">
    @unless($embedded)
        <x-surface-card :title="__('Manual Attendance Entry')" icon="icons.pending-icon">
            <p class="text-sm text-zinc-500">
                {{ __('Daily manual attendance input screen for organizations without a device system.') }}
            </p>
        </x-surface-card>
    @endunless

    @if($canWrite)
        <x-surface-card :title="__('Manual entry form')">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <x-label for="manual-form-tabel">{{ __('Tabel no') }}</x-label>
                    <x-livewire-input id="manual-form-tabel" mode="gray" name="form.tabel_no" wire:model.live.debounce.300ms="form.tabel_no" />
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
                <div>
                    <x-label for="manual-form-status">{{ __('Approval') }}</x-label>
                    <div id="manual-form-status" class="flex h-10 items-center rounded-lg bg-neutral-100 px-3 text-sm text-zinc-600 shadow-sm">
                        {{ __('Manual entry always starts as') }}
                        <span class="ms-1 font-semibold text-amber-600">{{ __('pending') }}</span>
                    </div>
                </div>
                <div>
                    <x-label for="manual-metric-override">{{ __('Metric input mode') }}</x-label>
                    <label for="manual-metric-override" class="flex h-10 items-center gap-2 rounded-lg bg-neutral-100 px-3 text-sm text-zinc-700 shadow-sm">
                        <input
                            id="manual-metric-override"
                            type="checkbox"
                            wire:model.live="manualMetricOverride"
                            class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span>{{ __('Manual override') }}</span>
                    </label>
                </div>

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

                <div class="md:col-span-3">
                    @if($form['shift_source_mode'] === 'explicit')
                        <div class="mb-3 rounded-xl border border-blue-100 bg-blue-50 p-3">
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

                    <p class="text-xs text-zinc-500">
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

            <div class="mt-4">
                <x-button mode="primary" wire:click="save">{{ __('Save') }}</x-button>
            </div>
        </x-surface-card>
    @endif

    <x-surface-card :title="__('Manual override queue')">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
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
                        __('Tabel no'),
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
                                <x-table.td extraClasses="font-medium font-mono uppercase !text-zinc-500">{{ $entry->tabel_no }}</x-table.td>
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
