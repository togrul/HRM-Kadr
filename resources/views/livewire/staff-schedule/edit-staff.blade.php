<div class="flex flex-col space-y-2">
    <div class="sidemenu-title">
        <h2 class="text-xl font-title font-semibold text-gray-500" id="slide-over-title">
          {{ $title ?? ''}}
        </h2>
    </div>
    @foreach ($this->staff as $key => $stf)
    <div
        @class([
          'grid grid-cols-1 gap-2',
          'sm:grid-cols-5' => !$hidePosition,
          'sm:grid-cols-3' => $hidePosition,
        ])
    >
      @if(!$hidePosition)
        <div class="flex flex-col sm:col-span-2">
          <x-select-list class="w-full" :title="__('Position')" mode="gray" :selected="$staff[$key]['position']['name']" name="positionId">
              <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPosition" wire:model.live="searchPosition"></x-livewire-input>

              <x-select-list-item wire:click="setData({{ $key }},'staff','position_id','position',null,null)" :selected="$staff[$key]['position_id'] == -1"
                wire:model='staff.position_id'>
                ---
              </x-select-list-item>
              @foreach($positions as $position)
              <x-select-list-item wire:click="setData({{ $key }},'staff','position_id','position','{{ $position->name }}',{{ $position->id }})"
                :selected="$position->id === $staff[$key]['position_id']" wire:model='staff.position_id'>
                {{ $position->name }}
              </x-select-list-item>
              @endforeach
          </x-select-list>
          @error("staff.$key.position_id")
          <x-validation> {{ $message }} </x-validation>
          @enderror
        </div>
        @endif
        <div class="flex flex-col">
            <x-label for="staff.{{ $key }}.total">{{ __('Total') }}</x-label>
            <x-livewire-input mode="gray" type="number" name="staff.{{ $key }}.total" wire:model.live="staff.{{ $key }}.total"></x-livewire-input>
            @error("staff.$key.total")
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="staff.{{ $key }}.filled">{{ __('Filled') }}</x-label>
            <x-livewire-input mode="gray" disabled="true" type="number" name="staff.{{ $key }}.filled" wire:model="staff.{{ $key }}.filled"></x-livewire-input>
            @error("staff.$key.filled")
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="staff.{{ $key }}.vacant">{{ __('Vacant') }}</x-label>
            <div class="flex space-x-2 items-center">
              <x-livewire-input mode="gray" type="number" name="staff.{{ $key }}.vacant" wire:model="staff.{{ $key }}.vacant"></x-livewire-input>
              <x-button mode="rose" wire:click="deleteRow({{ $key }})">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
              </x-button>
            </div>
            @error("staff.$key.vacant")
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>
    @endforeach
    <div class="flex">
        <x-button mode="black" wire:click="addRow">{{ __('Add') }}</x-button>
    </div>
    <div class="flex justify-between items-end w-full">
        <x-modal-button>{{ __('Save') }}</x-modal-button>
    </div>
</div>
