<div class="space-y-4">
    <x-surface-card :title="__('Shift definitions')" icon="icons.clock-icon">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <x-label for="attendance-shift-name">{{ __('Shift name') }}</x-label>
                <x-livewire-input id="attendance-shift-name" mode="gray" name="shiftForm.name" wire:model.defer="shiftForm.name" />
                @error('shiftForm.name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="attendance-shift-start">{{ __('Start time') }}</x-label>
                <input id="attendance-shift-start" type="time" wire:model.defer="shiftForm.start_time" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                @error('shiftForm.start_time') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="attendance-shift-end">{{ __('End time') }}</x-label>
                <input id="attendance-shift-end" type="time" wire:model.defer="shiftForm.end_time" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                @error('shiftForm.end_time') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="attendance-shift-break">{{ __('Break minutes') }}</x-label>
                <x-livewire-input id="attendance-shift-break" mode="gray" type="number" min="0" name="shiftForm.break_minutes" wire:model.defer="shiftForm.break_minutes" />
                @error('shiftForm.break_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="attendance-shift-in-before">{{ __('Check-in flex before (min)') }}</x-label>
                <x-livewire-input id="attendance-shift-in-before" mode="gray" type="number" min="0" name="shiftForm.in_flex_before_minutes" wire:model.defer="shiftForm.in_flex_before_minutes" />
            </div>
            <div>
                <x-label for="attendance-shift-in-after">{{ __('Check-in flex after (min)') }}</x-label>
                <x-livewire-input id="attendance-shift-in-after" mode="gray" type="number" min="0" name="shiftForm.in_flex_after_minutes" wire:model.defer="shiftForm.in_flex_after_minutes" />
            </div>
            <div>
                <x-label for="attendance-shift-out-before">{{ __('Check-out flex before (min)') }}</x-label>
                <x-livewire-input id="attendance-shift-out-before" mode="gray" type="number" min="0" name="shiftForm.out_flex_before_minutes" wire:model.defer="shiftForm.out_flex_before_minutes" />
            </div>
            <div>
                <x-label for="attendance-shift-out-after">{{ __('Check-out flex after (min)') }}</x-label>
                <x-livewire-input id="attendance-shift-out-after" mode="gray" type="number" min="0" name="shiftForm.out_flex_after_minutes" wire:model.defer="shiftForm.out_flex_after_minutes" />
            </div>
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label class="flex h-10 items-center gap-2 rounded-lg bg-neutral-100 px-3 text-sm text-zinc-700 shadow-sm">
                <input type="checkbox" wire:model.defer="shiftForm.is_night_shift" class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                <span>{{ __('Night shift') }}</span>
            </label>
            <label class="flex h-10 items-center gap-2 rounded-lg bg-neutral-100 px-3 text-sm text-zinc-700 shadow-sm">
                <input type="checkbox" wire:model.defer="shiftForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                <span>{{ __('Active shift') }}</span>
            </label>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <x-button mode="primary" wire:click="saveShift">
                {{ $editingShiftId ? __('Update shift') : __('Save shift') }}
            </x-button>
            @if($editingShiftId)
                <x-button mode="slate" wire:click="resetShiftForm">{{ __('Cancel edit') }}</x-button>
            @endif
        </div>
    </x-surface-card>

    <x-surface-card :title="__('Shift assignments')" icon="icons.network-icon">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <x-ui.filter-select
                    :label="__('Structure filter')"
                    :options="$structureOptions"
                    searchModel="structureSearch"
                    wire:model.live="selectedStructureId"
                    :placeholder="__('All structures')"
                />
            </div>
            <div>
                <x-ui.search-input-select
                    :label="__('Search personnel')"
                    searchModel="personnelSearch"
                    :selected="$selectedPersonnel"
                    displayKey="fullname"
                    idKey="tabel_no"
                    onClear="clearPersonnel"
                    clearField="tabel_no"
                    :placeholder="__('Search by name or tabel no')"
                >
                    @forelse($personnelResults as $personnel)
                        <button
                            type="button"
                            wire:click="selectPersonnel('{{ $personnel->tabel_no }}', '{{ addslashes($personnel->fullname) }}')"
                            class="flex w-full flex-col rounded-md px-2 py-1 text-left text-slate-600 transition-all duration-300 hover:bg-white drop-shadow-sm"
                        >
                            <span>{{ $personnel->fullname }}</span>
                            <span class="text-xs font-mono text-zinc-500">{{ $personnel->tabel_no }}</span>
                            <span class="text-[11px] text-zinc-400">
                                {{ $personnel->structure?->name }}
                                @if($personnel->position?->name)
                                    • {{ $personnel->position->name }}
                                @endif
                            </span>
                        </button>
                    @empty
                        <span class="mx-auto text-sm font-medium text-slate-500">
                            {{ __('Search personnel to assign a shift') }}
                        </span>
                    @endforelse
                </x-ui.search-input-select>
                @error('assignmentForm.tabel_no') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="attendance-assignment-shift">{{ __('Shift') }}</x-label>
                <select id="attendance-assignment-shift" wire:model.defer="assignmentForm.shift_id" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                    <option value="">{{ __('Select shift') }}</option>
                    @foreach($assignmentShifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                    @endforeach
                </select>
                @error('assignmentForm.shift_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="attendance-assignment-from">{{ __('Effective from') }}</x-label>
                <input id="attendance-assignment-from" type="date" wire:model.defer="assignmentForm.effective_from" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                @error('assignmentForm.effective_from') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="attendance-assignment-to">{{ __('Effective to') }}</x-label>
                <input id="attendance-assignment-to" type="date" wire:model.defer="assignmentForm.effective_to" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                @error('assignmentForm.effective_to') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        @if($selectedStructureId)
            <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                <x-small-badge mode="sky">{{ __('Structure scope') }}</x-small-badge>
                <span>{{ __('Personnel search and recent assignments are filtered by the selected structure tree.') }}</span>
                @if($selectedStructureLabel)
                    <span class="font-medium">{{ $selectedStructureLabel }}</span>
                @endif
            </div>
        @endif

        @if($selectedPersonnel)
            <div class="mt-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-zinc-900">{{ $selectedPersonnel['fullname'] }}</p>
                        <p class="text-xs font-mono uppercase tracking-wide text-zinc-500">{{ $selectedPersonnel['tabel_no'] }}</p>
                    </div>

                    @if($selectedPersonnelActiveAssignment?->shift)
                        <div class="flex flex-col items-start gap-1 text-xs text-zinc-500">
                            <x-small-badge mode="blue">{{ __('Current active shift') }}: {{ $selectedPersonnelActiveAssignment->shift->name }}</x-small-badge>
                            <span>{{ $selectedPersonnelActiveAssignment->shift->start_time }} - {{ $selectedPersonnelActiveAssignment->shift->end_time }}</span>
                        </div>
                    @else
                        <x-small-badge>{{ __('No active shift assignment') }}</x-small-badge>
                    @endif
                </div>
            </div>
        @endif

        <div class="mt-3">
            <label class="flex h-10 items-center gap-2 rounded-lg bg-neutral-100 px-3 text-sm text-zinc-700 shadow-sm">
                <input type="checkbox" wire:model.defer="assignmentForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                <span>{{ __('Active assignment') }}</span>
            </label>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <x-button mode="primary" wire:click="saveAssignment">
                {{ $editingAssignmentId ? __('Update assignment') : __('Save assignment') }}
            </x-button>
            @if($editingAssignmentId)
                <x-button mode="slate" wire:click="resetAssignmentForm">{{ __('Cancel edit') }}</x-button>
            @endif
        </div>
    </x-surface-card>

    <x-surface-card :title="__('Current shift catalog')">
        <div class="relative overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('Name'), __('Hours'), __('Break'), __('Flex window'), __('Status'), __('Actions')]">
                        @forelse($shifts as $shift)
                            <tr>
                                <x-table.td extraClasses="font-medium">{{ $shift->name }}</x-table.td>
                                <x-table.td>{{ $shift->start_time }} - {{ $shift->end_time }}</x-table.td>
                                <x-table.td>{{ $shift->break_minutes }} {{ __('min') }}</x-table.td>
                                <x-table.td>
                                    {{ __('In') }}: -{{ $shift->in_flex_before_minutes }}/+{{ $shift->in_flex_after_minutes }}
                                    <br>
                                    {{ __('Out') }}: -{{ $shift->out_flex_before_minutes }}/+{{ $shift->out_flex_after_minutes }}
                                </x-table.td>
                                <x-table.td>
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $shift->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                        {{ $shift->is_active ? __('active') : __('inactive') }}
                                    </span>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <div class="inline-flex items-center gap-2">
                                        <x-button mode="slate" class="!h-8 !px-3 !text-xs" wire:click="editShift({{ $shift->id }})">{{ __('Edit') }}</x-button>
                                        @if($shift->is_active)
                                            <x-button mode="reject" class="!h-8 !px-3 !text-xs" wire:click="deactivateShift({{ $shift->id }})">{{ __('Deactivate') }}</x-button>
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
    </x-surface-card>

    <x-surface-card :title="__('Recent shift assignments')">
        <div class="relative overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('Personnel'), __('Tabel no'), __('Structure'), __('Shift'), __('Effective range'), __('Source'), __('Status'), __('Actions')]">
                        @forelse($assignments as $assignment)
                            <tr>
                                <x-table.td extraClasses="font-medium">{{ $assignment->personnel?->fullname ?? '-' }}</x-table.td>
                                <x-table.td extraClasses="font-mono uppercase text-zinc-500">{{ $assignment->tabel_no }}</x-table.td>
                                <x-table.td>
                                    <div class="flex flex-col">
                                        <span>{{ $assignment->personnel?->structure?->name ?? '-' }}</span>
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
                                            {{ $assignment->effective_to?->format('Y-m-d') ?? __('open ended') }}
                                        </span>

                                        <div class="flex flex-wrap items-center gap-1">
                                            @if($assignment->effective_today)
                                                <x-small-badge mode="green">{{ __('Effective today') }}</x-small-badge>
                                            @endif

                                            @if($assignment->starts_in_future)
                                                <x-small-badge mode="sky">{{ __('Starts in future') }}</x-small-badge>
                                            @endif

                                            @if($assignment->is_expired)
                                                <x-small-badge>{{ __('Expired') }}</x-small-badge>
                                            @endif

                                            @if($assignment->has_overlap_warning)
                                                <x-small-badge mode="red">{{ __('Overlap warning') }}</x-small-badge>
                                            @endif
                                        </div>
                                    </div>
                                </x-table.td>
                                <x-table.td>{{ $assignment->assignment_source ?: 'manual_ui' }}</x-table.td>
                                <x-table.td>
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $assignment->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                            {{ $assignment->is_active ? __('active') : __('inactive') }}
                                        </span>

                                        @if($assignment->has_overlap_warning)
                                            <span class="text-xs text-rose-600">
                                                {{ __('This assignment overlaps another active range for the same personnel.') }}
                                            </span>
                                        @endif
                                    </div>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <div class="inline-flex items-center gap-2">
                                        <x-button mode="slate" class="!h-8 !px-3 !text-xs" wire:click="editAssignment({{ $assignment->id }})">{{ __('Edit') }}</x-button>
                                        @if($assignment->is_active)
                                            <x-button mode="reject" class="!h-8 !px-3 !text-xs" wire:click="deactivateAssignment({{ $assignment->id }})">{{ __('Deactivate') }}</x-button>
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
    </x-surface-card>
</div>
