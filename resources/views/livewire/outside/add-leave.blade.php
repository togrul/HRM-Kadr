<div class="flex flex-col space-y-4">
    <header class="sidemenu-title">
        <h2 class="text-xl font-title font-semibold text-gray-500" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </header>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <x-ui.search-input-select
            label="Search personnel"
            searchModel="personnelName"
            :selected="$leave['tabel_no'] ?? null"
            displayKey="fullname"
            idKey="tabel_no"
            onClear="removePersonnel"
            clearField="tabel_no"
            placeholder="Search..."
        >
            @forelse($this->personnelList as $pl)
                <p
                    wire:click="selectPersonnel('{{ $pl->tabel_no }}', '{{ $pl->fullname }}','tabel_no')"
                    class="cursor-pointer flex flex-col transition-all duration-300 hover:bg-white px-2 py-1 rounded-md text-slate-600 drop-shadow-sm"
                >
                    <span>{{ $pl->fullname }}</span>
                </p>
            @empty
                <span class="text-sm font-medium text-slate-500 mx-auto">
                    {{ __('Please search personnel') }}
                </span>
            @endforelse
        </x-ui.search-input-select>

        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('Leave type') }}"
                :options="$leaveTypes"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="leave.leave_type_id"
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
            @error("leave.ends_at")
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>

        <div class="flex flex-col">
            <x-label for="leave.total_days">{{ __('Total days') }}</x-label>
            <x-livewire-input mode="gray"  name="leave.total_days" wire:model="leave.total_days" disabled readonly></x-livewire-input>
            @error('leave.total_days')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1">
        <x-textarea name="leave.reason" :placeholder="__('Reason')" mode="gray" wire:model="leave.reason"></x-textarea>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 items-end">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('Status') }}"
                :options="$statuses"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="leave.status_id"
            />

            @error('leave.status_id')
            <x-validation>{{ $message }}</x-validation>
            @enderror
        </div>

        <x-ui.search-input-select
            label="Search personnel"
            searchModel="assignedSearch"
            :selected="$leave['assigned_to'] ?? null"
            displayKey="fullname"
            idKey="tabel_no"
            onClear="removePersonnel"
            clearField="assigned_to"
            placeholder="Search..."
        >
            @forelse($this->personnelList as $pl)
                <p
                    wire:click="selectPersonnel('{{ $pl->tabel_no }}', '{{ $pl->fullname }}','assigned_to')"
                    class="cursor-pointer flex flex-col transition-all duration-300 hover:bg-white px-2 py-1 rounded-md text-slate-600 drop-shadow-sm"
                >
                    <span>{{ $pl->fullname }}</span>
                </p>
            @empty
                <span class="text-sm font-medium text-slate-500 mx-auto">
                    {{ __('Please search personnel') }}
                </span>
            @endforelse
        </x-ui.search-input-select>

         <x-ui.file-upload
            model="leave.document_path"
            :data="$leave['document_path'] ?? []"
        />
    </div>
</div>
