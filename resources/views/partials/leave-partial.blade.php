<div class="flex flex-col space-y-4">
    <header class="sidemenu-title">
        <h2 class="text-xl font-semibold text-gray-500 font-title" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </header>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div class="flex flex-col">
             <x-ui.search-input-select
                label="{{ __('leaves::common.labels.search_personnel') }}"
                searchModel="personnelName"
                :selected="$leave->tabel_no"
                displayKey="fullname"
                idKey="tabel_no"
                onClear="removePersonnel"
                clearField="tabel_no"
                placeholder="{{ __('leaves::common.labels.search_placeholder') }}"
            >
                @forelse($this->applicantPersonnelList as $pl)
                    <p
                        wire:click="selectPersonnel('{{ $pl->tabel_no }}', '{{ $pl->fullname }}','tabel_no')"
                        class="flex flex-col px-2 py-1 transition-all duration-300 rounded-md cursor-pointer hover:bg-white text-slate-600 drop-shadow-sm"
                    >
                        <span>{{ $pl->fullname }}</span>
                    </p>
                @empty
                    <span class="mx-auto text-sm font-medium text-slate-500">
                        {{ __('leaves::common.labels.search_personnel') }}
                    </span>
                @endforelse
            </x-ui.search-input-select>
            @error('leave.tabel_no.tabel_no')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>

        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('leaves::common.labels.leave_type') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="leave.leave_type_id"
                :model="$this->leaveTypes"
            />
            
            @error('leave.leave_type_id')
                <x-validation> {{ $message }} </x-validation>
            @enderror

            @if($this->selectedLeaveTypeMeta)
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-small-badge mode="{{ data_get($this->selectedLeaveTypeMeta, 'max_days', 0) > 0 ? 'blue' : 'secondary' }}">
                        @if(data_get($this->selectedLeaveTypeMeta, 'max_days', 0) > 0)
                            {{ __('leaves::common.labels.max_days_short', ['days' => data_get($this->selectedLeaveTypeMeta, 'max_days')]) }}
                        @else
                            {{ __('leaves::common.labels.no_max_days') }}
                        @endif
                    </x-small-badge>

                    <x-small-badge mode="{{ data_get($this->selectedLeaveTypeMeta, 'requires_document') ? 'red' : 'green' }}">
                        {{ data_get($this->selectedLeaveTypeMeta, 'requires_document')
                            ? __('leaves::common.labels.document_required')
                            : __('leaves::common.labels.document_optional') }}
                    </x-small-badge>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('leaves::common.labels.duration_unit') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="leave.duration_unit"
                :model="$this->durationUnits"
            />
            @error('leave.duration_unit')
                <x-validation>{{ $message }}</x-validation>
            @enderror
        </div>

        @if ($leave->duration_unit === 'half_day')
            <div class="flex flex-col sm:col-span-1 lg:col-span-2">
                <x-ui.select-dropdown
                    label="{{ __('leaves::common.labels.partial_day_part') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="leave.partial_day_part"
                    :model="$this->partialDayParts"
                />
                @error('leave.partial_day_part')
                    <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        @endif

        @if ($leave->duration_unit === 'hour')
            <div class="flex flex-col">
                <x-label for="leave.starts_time">{{ __('leaves::common.labels.start_time') }}</x-label>
                <input id="leave.starts_time" type="time" wire:model.live="leave.starts_time" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                @error('leave.starts_time')
                    <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>

            <div class="flex flex-col">
                <x-label for="leave.ends_time">{{ __('leaves::common.labels.end_time') }}</x-label>
                <input id="leave.ends_time" type="time" wire:model.live="leave.ends_time" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                @error('leave.ends_time')
                    <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        <div class="flex flex-col">
            <x-label for="leave.starts_at">{{ __('leaves::common.labels.start_date') }}</x-label>
            <x-pikaday-input mode="gray" name="leave.starts_at" format="Y-MM-DD" wire:model.live="leave.starts_at">
                <x-slot name="script">
                    $el.onchange = function () {
                        @this.set('leave.starts_at', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error("leave.starts_at")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>

        <div class="flex flex-col">
            <x-label for="leave.ends_at">{{ __('leaves::common.labels.end_date') }}</x-label>
            <x-pikaday-input mode="gray" name="leave.ends_at" format="Y-MM-DD" wire:model.live="leave.ends_at">
                <x-slot name="script">
                    $el.onchange = function () {
                        @this.set('leave.ends_at', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error("leave.ends_at")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>

        <div class="flex flex-col">
            <x-label for="leave.total_days">{{ __('leaves::common.labels.total_days') }}</x-label>
            <x-livewire-input mode="gray"  name="leave.total_days" wire:model="leave.total_days" disabled readonly></x-livewire-input>
            @if($this->leaveDurationSummary)
                <p class="mt-2 text-xs font-medium text-zinc-500">{{ $this->leaveDurationSummary }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1">
        <x-textarea name="leave.reason" :placeholder="__('leaves::common.labels.reason')" mode="gray" wire:model="leave.reason"></x-textarea>
    </div>

    @if($this->leaveDurationNotice)
        <div class="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 10-1.5 0v4.5a.75.75 0 001.5 0v-4.5zM10 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="font-semibold">{{ __('leaves::common.messages.max_days_notice_title') }}</p>
                    <p>
                        {{ __('leaves::common.messages.max_days_notice_body', [
                            'type' => data_get($this->leaveDurationNotice, 'type_name'),
                            'selected' => data_get($this->leaveDurationNotice, 'selected_days'),
                            'max' => data_get($this->leaveDurationNotice, 'max_days'),
                        ]) }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[minmax(0,0.72fr)_minmax(0,1.28fr)]">
        <div class="grid gap-4">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('leaves::common.labels.status') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.defer="leave.status_id"
                    :model="$this->statuses"
                />

                @error('leave.status_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>

            <div class="flex flex-col">
                <x-ui.file-upload
                    model="leave.document_path"
                    :data="$leave->document_path"
                />

                @error('leave.document_path')
                    <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>

        <div class="flex min-w-0 flex-col">
            <x-label>{{ __('leaves::common.labels.assigned_person') }}</x-label>
            <div class="mt-1 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="setAssignmentMode('auto')"
                        @class([
                            'inline-flex items-center rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-tight transition',
                            'border-zinc-950 bg-zinc-950 text-white shadow-sm' => $leave->assignment_mode === 'auto',
                            'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:text-zinc-900' => $leave->assignment_mode !== 'auto',
                        ])
                    >
                        {{ __('leaves::common.labels.assignment_modes.auto') }}
                    </button>
                    <button
                        type="button"
                        wire:click="setAssignmentMode('manual')"
                        @class([
                            'inline-flex items-center rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-tight transition',
                            'border-zinc-950 bg-zinc-950 text-white shadow-sm' => $leave->assignment_mode === 'manual',
                            'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:text-zinc-900' => $leave->assignment_mode !== 'manual',
                        ])
                    >
                        {{ __('leaves::common.labels.assignment_modes.manual') }}
                    </button>
                </div>

                @if($leave->assignment_mode === 'auto')
                    @php($assignmentPreview = $this->assignmentPreview)
                    <div class="mt-4 space-y-4">
                        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-small-badge mode="sky">{{ __('leaves::common.labels.automatic_hierarchy') }}</x-small-badge>
                                <x-small-badge mode="{{ ($assignmentPreview['route']['hr_always_included'] ?? false) ? 'green' : 'secondary' }}">
                                    {{ ($assignmentPreview['route']['hr_always_included'] ?? false)
                                        ? __('leaves::common.labels.hr_active')
                                        : __('leaves::common.labels.hr_inactive') }}
                                </x-small-badge>
                                @if(data_get($assignmentPreview, 'route.approval_route_source'))
                                    <x-small-badge mode="secondary">
                                        {{ __('leaves::common.labels.route_source') }}:
                                        {{ __('leaves::common.labels.route_sources.'.data_get($assignmentPreview, 'route.approval_route_source')) }}
                                    </x-small-badge>
                                @endif
                            </div>

                            <p class="mt-3 text-sm leading-6 text-zinc-600">
                                {{ __('leaves::common.messages.automatic_assignment_help') }}
                            </p>
                        </div>

                        <div class="grid gap-3 xl:grid-cols-3">
                            <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('leaves::common.labels.primary_step') }}</x-ui.field-label>
                                <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-950">{{ data_get($assignmentPreview, 'approver.fullname') }}</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-600">{{ data_get($assignmentPreview, 'approver.position') }}</p>
                            </div>
                            <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('leaves::common.labels.upper_step') }}</x-ui.field-label>
                                @if(data_get($assignmentPreview, 'upper_enabled'))
                                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-950">{{ data_get($assignmentPreview, 'fallback.fullname') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-600">{{ data_get($assignmentPreview, 'fallback.position') }}</p>
                                @elseif(data_get($assignmentPreview, 'upper_candidate.id'))
                                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-950">{{ data_get($assignmentPreview, 'upper_candidate.fullname') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-600">{{ __('leaves::common.messages.upper_step_inactive_help') }}</p>
                                @else
                                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-950">{{ __('leaves::common.empty.not_assigned') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-600">{{ __('leaves::common.empty.no_upper_approver') }}</p>
                                @endif
                            </div>
                            <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('leaves::common.labels.selected_assignee') }}</x-ui.field-label>
                                <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-950">{{ data_get($leave->assigned_to, 'fullname', __('leaves::common.empty.not_assigned')) }}</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-600">{{ data_get($assignmentPreview, 'approver.position', '—') }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('leaves::common.labels.hierarchy_chain') }}</x-ui.field-label>
                            @if($assignmentPreview['chain'] === [])
                                <p class="mt-3 text-sm text-zinc-500">{{ __('leaves::common.empty.no_hierarchy_chain') }}</p>
                            @else
                                <div class="mt-3 space-y-3">
                                    @foreach($assignmentPreview['chain'] as $index => $row)
                                        <div class="flex items-start gap-3 rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                            <div class="mt-1 h-3 w-3 rounded-full bg-zinc-950 ring-4 ring-zinc-100"></div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['fullname'] }}</p>
                                                    <x-small-badge mode="secondary">
                                                        {{ $index === 0 ? __('leaves::common.labels.direct_manager') : __('leaves::common.labels.upper_line') }}
                                                    </x-small-badge>
                                                </div>
                                                <p class="mt-1 text-xs leading-5 text-zinc-600">{{ $row['position'] }}</p>
                                                <p class="mt-1 text-xs leading-5 text-zinc-500">{{ $row['structure'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        <p class="text-sm leading-6 text-zinc-600">{{ __('leaves::common.messages.manual_assignment_help') }}</p>
                        <x-ui.search-input-select
                            label="{{ __('leaves::common.labels.manual_assignee') }}"
                            searchModel="assignedSearch"
                            :selected="$leave->assigned_to"
                            displayKey="fullname"
                            idKey="id"
                            onClear="removePersonnel"
                            clearField="assigned_to"
                            placeholder="{{ __('leaves::common.labels.search_placeholder') }}"
                        >
                            @forelse($this->assignedPersonnelList as $pl)
                                <p
                                    wire:click="selectPersonnel('{{ $pl->tabel_no }}', '{{ $pl->fullname }}','assigned_to', {{ $pl->id }})"
                                    class="flex flex-col rounded-md px-2 py-1 text-slate-600 transition-all duration-300 drop-shadow-sm hover:bg-white"
                                >
                                    <span>{{ $pl->fullname }}</span>
                                </p>
                            @empty
                                <span class="mx-auto text-sm font-medium text-slate-500">
                                    {{ __('leaves::common.labels.search_personnel') }}
                                </span>
                            @endforelse
                        </x-ui.search-input-select>
                    </div>
                @endif
            </div>

            @error('leave.assigned_to.id')
                <x-validation>{{ $message }}</x-validation>
            @enderror
        </div>
    </div>

    <div class="flex items-end justify-between w-full">
        <x-modal-button>{{ __('leaves::common.actions.save') }}</x-modal-button>
    </div>
</div>
