<div class="flex flex-col space-y-2">
    <div class="sidemenu-title">
        <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
          {{ $title ?? ''}}
        </h2>
    </div>
    <div class="grid grid-cols-2 gap-2">
        <div class="flex flex-col">
            <x-select-list class="w-full" :title="__('Structure')" mode="gray" :selected="$structureName" name="structureId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchStructure" wire:model.live="searchStructure"></x-livewire-input>
                
                <x-select-list-item wire:click="setData('staff','structure_id','structure','---',null)" :selected="'---' == $structureName"
                  wire:model='staff.structure_id'>
                  ---
                </x-select-list-item>
                @foreach($structures as $structure)
                <x-select-list-item wire:click="setData('staff','structure_id','structure','{{ $structure->name }}',{{ $structure->id }})"
                  :selected="$structure->id === $structureId" wire:model='staff.structure_id'>
                  {{ $structure->name }}
                </x-select-list-item>
                @endforeach
            </x-select-list>
            @error('staff.structure_id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
          </div>
          <div class="flex flex-col">
            <x-select-list class="w-full" :title="__('Position')" mode="gray" :selected="$positionName" name="positionId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPosition" wire:model.live="searchPosition"></x-livewire-input>
                
                <x-select-list-item wire:click="setData('staff','position_id','position','---',null)" :selected="'---' == $positionName"
                  wire:model='staff.position_id'>
                  ---
                </x-select-list-item>
                @foreach($positions as $position)
                <x-select-list-item wire:click="setData('staff','position_id','position','{{ $position->name }}',{{ $position->id }})"
                  :selected="$position->id === $positionId" wire:model='staff.position_id'>
                  {{ $position->name }}
                </x-select-list-item>
                @endforeach
            </x-select-list>
            @error('staff.position_id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
          </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <div class="flex flex-col">
            <x-label for="staff.total">{{ __('Total') }}</x-label>
            <x-livewire-input mode="gray" type="number" name="staff.total" wire:model="staff.total"></x-livewire-input>
            @error('staff.total')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="staff.filled">{{ __('Filled') }}</x-label>
            <x-livewire-input mode="gray" type="number" name="staff.filled" wire:model="staff.filled"></x-livewire-input>
            @error('staff.filled')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="staff.vacant">{{ __('Vacant') }}</x-label>
            <x-livewire-input mode="gray" type="number" name="staff.vacant" wire:model="staff.vacant"></x-livewire-input>
            @error('staff.vacant')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    <div class="flex justify-between items-end w-full">
        <x-modal-button>{{ __('Save') }}</x-modal-button>
    </div>
</div>
