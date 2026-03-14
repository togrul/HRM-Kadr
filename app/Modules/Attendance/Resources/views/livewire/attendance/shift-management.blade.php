<div class="space-y-4">
    @island(name: 'attendance-shift-definitions')
    <div class="space-y-4">
        <x-surface-card :title="__('attendance::shift_management.titles.definitions')" icon="icons.clock-icon">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <x-label for="attendance-shift-name">{{ __('attendance::shift_management.fields.shift_name') }}</x-label>
                    <x-livewire-input id="attendance-shift-name" mode="gray" name="shiftForm.name" wire:model.defer="shiftForm.name" />
                    @error('shiftForm.name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="attendance-shift-start">{{ __('attendance::shift_management.fields.start_time') }}</x-label>
                    <input id="attendance-shift-start" type="time" wire:model.defer="shiftForm.start_time" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                    @error('shiftForm.start_time') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="attendance-shift-end">{{ __('attendance::shift_management.fields.end_time') }}</x-label>
                    <input id="attendance-shift-end" type="time" wire:model.defer="shiftForm.end_time" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                    @error('shiftForm.end_time') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="attendance-shift-break">{{ __('attendance::shift_management.fields.break_minutes') }}</x-label>
                    <x-livewire-input id="attendance-shift-break" mode="gray" type="number" min="0" name="shiftForm.break_minutes" wire:model.defer="shiftForm.break_minutes" />
                    @error('shiftForm.break_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="attendance-shift-in-before">{{ __('attendance::shift_management.fields.check_in_flex_before') }}</x-label>
                    <x-livewire-input id="attendance-shift-in-before" mode="gray" type="number" min="0" name="shiftForm.in_flex_before_minutes" wire:model.defer="shiftForm.in_flex_before_minutes" />
                </div>
                <div>
                    <x-label for="attendance-shift-in-after">{{ __('attendance::shift_management.fields.check_in_flex_after') }}</x-label>
                    <x-livewire-input id="attendance-shift-in-after" mode="gray" type="number" min="0" name="shiftForm.in_flex_after_minutes" wire:model.defer="shiftForm.in_flex_after_minutes" />
                </div>
                <div>
                    <x-label for="attendance-shift-out-before">{{ __('attendance::shift_management.fields.check_out_flex_before') }}</x-label>
                    <x-livewire-input id="attendance-shift-out-before" mode="gray" type="number" min="0" name="shiftForm.out_flex_before_minutes" wire:model.defer="shiftForm.out_flex_before_minutes" />
                </div>
                <div>
                    <x-label for="attendance-shift-out-after">{{ __('attendance::shift_management.fields.check_out_flex_after') }}</x-label>
                    <x-livewire-input id="attendance-shift-out-after" mode="gray" type="number" min="0" name="shiftForm.out_flex_after_minutes" wire:model.defer="shiftForm.out_flex_after_minutes" />
                </div>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <label class="flex h-10 items-center gap-2 rounded-lg bg-neutral-100 px-3 text-sm text-zinc-700 shadow-sm">
                    <input type="checkbox" wire:model.defer="shiftForm.is_night_shift" class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                    <span>{{ __('attendance::shift_management.labels.night_shift') }}</span>
                </label>
                <label class="flex h-10 items-center gap-2 rounded-lg bg-neutral-100 px-3 text-sm text-zinc-700 shadow-sm">
                    <input type="checkbox" wire:model.defer="shiftForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                    <span>{{ __('attendance::shift_management.labels.active_shift') }}</span>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-button mode="primary" wire:click="saveShift">
                    {{ $editingShiftId ? __('attendance::shift_management.actions.update_shift') : __('attendance::shift_management.actions.save_shift') }}
                </x-button>
                @if($editingShiftId)
                    <x-button mode="slate" wire:click="resetShiftForm">{{ __('attendance::shift_management.actions.cancel_edit') }}</x-button>
                @endif
            </div>
        </x-surface-card>

        <div class="space-y-3">
            <div class="relative overflow-x-auto">
                <div class="inline-block min-w-full py-2 align-middle">
                    <div class="overflow-visible">
                        <x-table.tbl :headers="[__('attendance::shift_management.table.name'), __('attendance::shift_management.table.hours'), __('attendance::shift_management.table.break'), __('attendance::shift_management.table.flex_window'), __('attendance::shift_management.table.status'), __('attendance::shift_management.table.actions')]" :title="__('attendance::shift_management.titles.current_catalog')">
                            @forelse($this->shifts as $shift)
                                <tr>
                                    <x-table.td extraClasses="font-medium">{{ $shift->name }}</x-table.td>
                                    <x-table.td>{{ $shift->start_time }} - {{ $shift->end_time }}</x-table.td>
                                    <x-table.td>{{ $shift->break_minutes }} {{ __('attendance::shift_management.labels.min') }}</x-table.td>
                                    <x-table.td>
                                        {{ __('attendance::shift_management.labels.in') }}: -{{ $shift->in_flex_before_minutes }}/+{{ $shift->in_flex_after_minutes }}
                                        <br>
                                        {{ __('attendance::shift_management.labels.out') }}: -{{ $shift->out_flex_before_minutes }}/+{{ $shift->out_flex_after_minutes }}
                                    </x-table.td>
                                    <x-table.td>
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $shift->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                            {{ $shift->is_active ? __('attendance::shift_management.statuses.active') : __('attendance::shift_management.statuses.inactive') }}
                                        </span>
                                    </x-table.td>
                                    <x-table.td :isButton="true">
                                        <div class="inline-flex items-center gap-2">
                                            <a
                                                href="{{ route('attendance', ['tab' => 'history', 'history_type' => 'shift', 'history_subject_id' => $shift->id]) }}"
                                                class="inline-flex h-8 items-center justify-center rounded-lg bg-sky-50 px-3 text-xs font-medium text-sky-700 transition hover:bg-sky-100"
                                            >
                                                {{ __('attendance::history.actions.open_filtered_history') }}
                                            </a>
                                            <x-button mode="slate" class="!h-8 !px-3 !text-xs" wire:click="editShift({{ $shift->id }})">{{ __('attendance::shift_management.actions.edit') }}</x-button>
                                            @if($shift->is_active)
                                                <x-button mode="reject" class="!h-8 !px-3 !text-xs" wire:click="deactivateShift({{ $shift->id }})">{{ __('attendance::shift_management.actions.deactivate') }}</x-button>
                                            @endif
                                        </div>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty :rows="6" />
                            @endforelse
                        </x-table.tbl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endisland

    <div class="space-y-4">
        <x-surface-card :title="__('attendance::shift_management.titles.assignments')" icon="icons.network-icon">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <x-ui.filter-select
                        :label="__('attendance::shift_management.fields.structure_filter')"
                        :options="$this->structureOptions"
                        searchModel="structureSearch"
                        wire:model.live="selectedStructureId"
                        :placeholder="__('attendance::shift_management.options.all_structures')"
                    />
                </div>
                <div>
                    <x-ui.search-input-select
                        :label="__('attendance::shift_management.fields.search_personnel')"
                        searchModel="personnelSearch"
                        :selected="$selectedPersonnel"
                        displayKey="fullname"
                        idKey="tabel_no"
                        onClear="clearPersonnel"
                        clearField="tabel_no"
                        :placeholder="__('attendance::shift_management.fields.search_personnel')"
                    >
                        @forelse($this->personnelResults as $personnel)
                            <button
                                type="button"
                                wire:click="selectPersonnel('{{ $personnel->tabel_no }}', '{{ addslashes($personnel->fullname) }}')"
                                class="flex w-full flex-col rounded-md px-2 py-1 text-left text-slate-600 transition-all duration-300 hover:bg-white drop-shadow-sm"
                            >
                                <span>{{ $personnel->fullname }}</span>
                                <span class="text-xs font-mono text-zinc-500">{{ $personnel->tabel_no }}</span>
                                <span class="max-w-[18rem] truncate text-[11px] text-zinc-400 md:max-w-[24rem]" title="{{ $personnel->structure_path }}">
                                    {{ $personnel->structure_path ?: '-' }}
                                    @if($personnel->position?->name)
                                        • {{ $personnel->position->name }}
                                    @endif
                                </span>
                            </button>
                        @empty
                            <span class="mx-auto text-sm font-medium text-slate-500">
                                {{ __('attendance::shift_management.labels.search_personnel_empty') }}
                            </span>
                        @endforelse
                    </x-ui.search-input-select>
                    @error('assignmentForm.tabel_no') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="attendance-assignment-shift">{{ __('attendance::shift_management.fields.shift') }}</x-label>
                    <select id="attendance-assignment-shift" wire:model.defer="assignmentForm.shift_id" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                        <option value="">{{ __('attendance::shift_management.options.select_shift') }}</option>
                        @foreach($this->assignmentShifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                        @endforeach
                    </select>
                    @error('assignmentForm.shift_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="attendance-assignment-from">{{ __('attendance::shift_management.fields.effective_from') }}</x-label>
                    <input id="attendance-assignment-from" type="date" wire:model.defer="assignmentForm.effective_from" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                    @error('assignmentForm.effective_from') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="attendance-assignment-to">{{ __('attendance::shift_management.fields.effective_to') }}</x-label>
                    <input id="attendance-assignment-to" type="date" wire:model.defer="assignmentForm.effective_to" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                    @error('assignmentForm.effective_to') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            @if($selectedStructureId)
                <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                    <x-small-badge mode="sky">{{ __('attendance::shift_management.labels.structure_scope') }}</x-small-badge>
                    <span>{{ __('attendance::shift_management.labels.scope_description') }}</span>
                    @if($this->selectedStructureLabel)
                        <span class="font-medium">{{ $this->selectedStructureLabel }}</span>
                    @endif
                </div>
            @endif

            @if($selectedPersonnel)
                <div class="mt-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-900">{{ $selectedPersonnel['fullname'] }}</p>
                            <p class="text-xs font-mono uppercase tracking-wide text-zinc-500">{{ $selectedPersonnel['tabel_no'] }}</p>
                            @if($this->selectedPersonnelRecord?->structure_path)
                                <p class="mt-1 max-w-[18rem] truncate text-xs text-zinc-500 md:max-w-[24rem]" title="{{ $this->selectedPersonnelRecord->structure_path }}">
                                    {{ $this->selectedPersonnelRecord->structure_path }}
                                    @if($this->selectedPersonnelRecord?->position?->name)
                                        • {{ $this->selectedPersonnelRecord->position->name }}
                                    @endif
                                </p>
                            @endif
                        </div>

                        @if($this->selectedPersonnelActiveAssignment?->shift)
                            <div class="flex flex-col items-start gap-1 text-xs text-zinc-500">
                                <x-small-badge mode="blue">{{ __('attendance::shift_management.labels.current_active_shift') }}: {{ $this->selectedPersonnelActiveAssignment->shift->name }}</x-small-badge>
                                <span>{{ $this->selectedPersonnelActiveAssignment->shift->start_time }} - {{ $this->selectedPersonnelActiveAssignment->shift->end_time }}</span>
                            </div>
                        @else
                            <x-small-badge>{{ __('attendance::shift_management.labels.no_active_shift_assignment') }}</x-small-badge>
                        @endif
                    </div>
                </div>
            @endif

            <div class="mt-3">
                <label class="flex h-10 items-center gap-2 rounded-lg bg-neutral-100 px-3 text-sm text-zinc-700 shadow-sm">
                    <input type="checkbox" wire:model.defer="assignmentForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                    <span>{{ __('attendance::shift_management.labels.active_assignment') }}</span>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-button mode="primary" wire:click="saveAssignment">
                    {{ $editingAssignmentId ? __('attendance::shift_management.actions.update_assignment') : __('attendance::shift_management.actions.save_assignment') }}
                </x-button>
                @if($editingAssignmentId)
                    <x-button mode="slate" wire:click="resetAssignmentForm">{{ __('attendance::shift_management.actions.cancel_edit') }}</x-button>
                @endif
            </div>
        </x-surface-card>

        <div class="space-y-3">
            <div class="relative overflow-x-auto">
                <div class="inline-block min-w-full py-2 align-middle">
                    <div class="overflow-visible">
                        <x-table.tbl :headers="[__('attendance::shift_management.table.personnel'), __('attendance::shift_management.table.tabel_no'), __('attendance::shift_management.table.structure'), __('attendance::shift_management.table.shift'), __('attendance::shift_management.table.effective_range'), __('attendance::shift_management.table.source'), __('attendance::shift_management.table.status'), __('attendance::shift_management.table.actions')]" :title="__('attendance::shift_management.titles.recent_assignments')">
                            @forelse($this->assignments as $assignment)
                                <tr>
                                    <x-table.td extraClasses="font-medium">{{ $assignment->personnel?->fullname ?? '-' }}</x-table.td>
                                    <x-table.td extraClasses="font-mono uppercase text-zinc-500">{{ $assignment->tabel_no }}</x-table.td>
                                    <x-table.td>
                                        <div class="flex flex-col">
                                            <span class="max-w-[18rem] truncate md:max-w-[24rem]" title="{{ $assignment->personnel?->structure_path ?? '' }}">
                                                {{ $assignment->personnel?->structure_path ?? '-' }}
                                            </span>
                                            @if($assignment->personnel?->position?->name)
                                                <span class="text-xs text-zinc-500">{{ $assignment->personnel->position->name }}</span>
                                            @endif
                                        </div>
                                    </x-table.td>
                                    <x-table.td>{{ $assignment->shift?->name ?? '-' }}</x-table.td>
                                    <x-table.td>
                                        <div class="flex flex-col gap-1">
                                            <span>
                                                {{ $assignment->effective_from?->format('Y-m-d') ?? '-' }}
                                                -
                                                {{ $assignment->effective_to?->format('Y-m-d') ?? __('attendance::shift_management.options.open_ended') }}
                                            </span>

                                            <div class="flex flex-wrap items-center gap-1">
                                                @if($assignment->effective_today)
                                                    <x-small-badge mode="green">{{ __('attendance::shift_management.labels.effective_today') }}</x-small-badge>
                                                @endif

                                                @if($assignment->starts_in_future)
                                                    <x-small-badge mode="sky">{{ __('attendance::shift_management.labels.starts_in_future') }}</x-small-badge>
                                                @endif

                                                @if($assignment->is_expired)
                                                    <x-small-badge>{{ __('attendance::shift_management.labels.expired') }}</x-small-badge>
                                                @endif

                                                @if($assignment->has_overlap_warning)
                                                    <x-small-badge mode="red">{{ __('attendance::shift_management.labels.overlap_warning') }}</x-small-badge>
                                                @endif
                                            </div>
                                        </div>
                                    </x-table.td>
                                    <x-table.td>
                                        @php
                                            $assignmentSource = (string) ($assignment->assignment_source ?: 'manual_ui');
                                            $assignmentSourceMode = match ($assignmentSource) {
                                                'manual_ui', 'manual' => 'sky',
                                                'system' => 'green',
                                                'import' => 'amber',
                                                default => 'secondary',
                                            };
                                            $assignmentSourceLabel = __('attendance::shift_management.sources.'.$assignmentSource);
                                            if ($assignmentSourceLabel === 'attendance::shift_management.sources.'.$assignmentSource) {
                                                $assignmentSourceLabel = str($assignmentSource)
                                                    ->replace('_', ' ')
                                                    ->headline()
                                                    ->toString();
                                            }
                                        @endphp
                                        <x-small-badge :mode="$assignmentSourceMode">
                                            {{ $assignmentSourceLabel }}
                                        </x-small-badge>
                                    </x-table.td>
                                    <x-table.td>
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $assignment->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                                {{ $assignment->is_active ? __('attendance::shift_management.statuses.active') : __('attendance::shift_management.statuses.inactive') }}
                                            </span>

                                            @if($assignment->has_overlap_warning)
                                                <span class="text-xs text-rose-600">
                                                    {{ __('attendance::shift_management.labels.overlap_description') }}
                                                </span>
                                            @endif
                                        </div>
                                    </x-table.td>
                                    <x-table.td :isButton="true">
                                        <div class="inline-flex items-center gap-2">
                                            <a
                                                href="{{ route('attendance', ['tab' => 'history', 'history_type' => 'assignment', 'history_subject_id' => $assignment->id]) }}"
                                                class="inline-flex h-8 items-center justify-center rounded-lg bg-sky-50 px-3 text-xs font-medium text-sky-700 transition hover:bg-sky-100"
                                            >
                                                {{ __('attendance::history.actions.open_filtered_history') }}
                                            </a>
                                            <x-button mode="slate" class="!h-8 !px-3 !text-xs" wire:click="editAssignment({{ $assignment->id }})">{{ __('attendance::shift_management.actions.edit') }}</x-button>
                                            @if($assignment->is_active)
                                                <x-button mode="reject" class="!h-8 !px-3 !text-xs" wire:click="deactivateAssignment({{ $assignment->id }})">{{ __('attendance::shift_management.actions.deactivate') }}</x-button>
                                            @endif
                                        </div>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty :rows="6" />
                            @endforelse
                        </x-table.tbl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
