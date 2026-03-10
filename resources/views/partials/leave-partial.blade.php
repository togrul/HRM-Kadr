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
                @forelse($this->personnelList as $pl)
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
        </div>

        <div class="flex flex-col">
            <x-label for="leave.total_days">{{ __('leaves::common.labels.total_days') }}</x-label>
            <x-livewire-input mode="gray"  name="leave.total_days" wire:model="leave.total_days" disabled readonly></x-livewire-input>
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

    <div class="grid items-start items-end grid-cols-1 gap-2 sm:grid-cols-3">
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
            <x-ui.search-input-select
                label="{{ __('leaves::common.labels.assigned_person') }}"
                searchModel="assignedSearch"
                :selected="$leave->assigned_to"
                displayKey="fullname"
                idKey="id"
                onClear="removePersonnel"
                clearField="assigned_to"
                placeholder="{{ __('leaves::common.labels.search_placeholder') }}"
            >
                @forelse($this->personnelList as $pl)
                    <p
                        wire:click="selectPersonnel('{{ $pl->tabel_no }}', '{{ $pl->fullname }}','assigned_to', {{ $pl->id }})"
                        class="flex flex-col px-2 py-1 transition-all duration-300 rounded-md cursor-pointer hover:bg-white text-slate-600 drop-shadow-sm"
                    >
                        <span>{{ $pl->fullname }}</span>
                    </p>
                @empty
                    <span class="mx-auto text-sm font-mediu m text-slate-500">
                        {{ __('leaves::common.labels.search_personnel') }}
                    </span>
                @endforelse
            </x-ui.search-input-select>
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

    <div class="flex items-end justify-between w-full">
        <x-modal-button>{{ __('leaves::common.actions.save') }}</x-modal-button>
    </div>
</div>
