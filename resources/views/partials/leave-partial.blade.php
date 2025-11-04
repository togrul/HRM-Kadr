<div class="flex flex-col space-y-4">
    <header class="sidemenu-title">
        <h2 class="text-xl font-semibold text-gray-500 font-title" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </header>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div class="flex flex-col">
             <x-ui.search-input-select
                label="Search personnel"
                searchModel="personnelName"
                :selected="$leave->tabel_no"
                displayKey="fullname"
                idKey="tabel_no"
                onClear="removePersonnel"
                clearField="tabel_no"
                placeholder="Search..."
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
                        {{ __('Please search personnel') }}
                    </span>
                @endforelse
            </x-ui.search-input-select>
            @error('leave.tabel_no.tabel_no')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>

        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('Leave type') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="leave.leave_type_id"
                :model="$this->leaveTypes"
            />
            
            @error('leave.leave_type_id')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        <div class="flex flex-col">
            <x-label for="leave.starts_at">{{ __('Start date') }}</x-label>
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
            <x-label for="leave.ends_at">{{ __('End date') }}</x-label>
            <x-pikaday-input mode="gray" name="leave.ends_at" format="Y-MM-DD" wire:model.live="leave.ends_at">
                <x-slot name="script">
                    $el.onchange = function () {
                        @this.set('leave.ends_at', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
        </div>

        <div class="flex flex-col">
            <x-label for="leave.total_days">{{ __('Total days') }}</x-label>
            <x-livewire-input mode="gray"  name="leave.total_days" wire:model="leave.total_days" disabled readonly></x-livewire-input>
        </div>
    </div>

    <div class="grid grid-cols-1">
        <x-textarea name="leave.reason" :placeholder="__('Reason')" mode="gray" wire:model="leave.reason"></x-textarea>
    </div>

    <div class="grid items-start grid-cols-1 gap-2 sm:grid-cols-3">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('Status') }}"
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

        <x-ui.search-input-select
            label="Assigned person"
            searchModel="assignedSearch"
            :selected="$leave->assigned_to"
            displayKey="fullname"
            idKey="tabel_no"
            onClear="removePersonnel"
            clearField="assigned_to"
            placeholder="Search..."
        >
            @forelse($this->personnelList as $pl)
                <p
                    wire:click="selectPersonnel('{{ $pl->tabel_no }}', '{{ $pl->fullname }}','assigned_to')"
                    class="flex flex-col px-2 py-1 transition-all duration-300 rounded-md cursor-pointer hover:bg-white text-slate-600 drop-shadow-sm"
                >
                    <span>{{ $pl->fullname }}</span>
                </p>
            @empty
                <span class="mx-auto text-sm font-mediu m text-slate-500">
                    {{ __('Please search personnel') }}
                </span>
            @endforelse
        </x-ui.search-input-select>

         <x-ui.file-upload
            model="leave.document_path"
            :data="$leave->document_path"
        />
    </div>

    <div class="flex items-end justify-between w-full">
        <x-modal-button>{{ __('Save') }}</x-modal-button>
    </div>
</div>
