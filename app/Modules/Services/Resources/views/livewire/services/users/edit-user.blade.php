<div>
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            {{ $title ?? ''}}
          </h2>
    </div>

    <div
        class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
    >

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
      <div class="">
        <x-label for="user.name">{{ __('Name') }}</x-label>
        <x-livewire-input mode="gray" name="user.name" wire:model="user.name"></x-livewire-input>
        @error('user.name')
            <x-validation> {{ $message }} </x-validation>
        @enderror
      </div>
      <div class="">
        <x-label for="user.email">{{ __('Email') }}</x-label>
        <x-livewire-input mode="gray" name="user.email" wire:model="user.email"></x-livewire-input>
        @error('user.email')
          <x-validation> {{ $message }} </x-validation>
        @enderror
      </div>
       <div>
            <x-ui.select-dropdown
                :label="__('Role')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="roleId"
                :model="$this->roleOptions"
                    search-model="search.role"
            >
            </x-ui.select-dropdown>
        @error('roleId')
          <x-validation> {{ $message }} </x-validation>
        @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div class="">
          <x-label for="user.password">{{ __('Password') }}</x-label>
          <x-livewire-input mode="gray" type="password" name="user.password" wire:model="user.password" autocomplete="new-password"></x-livewire-input>
          @error('user.password')
              <x-validation> {{ $message }} </x-validation>
          @enderror
        </div>
        <div class="">
          <x-label for="user.confirm-password">{{ __('Confirm Password') }}</x-label>
          <x-livewire-input mode="gray" type="password" name="user.confirm-password" wire:model="user.confirm-password"></x-livewire-input>
          @error('user.confirm-password')
              <x-validation> {{ $message }} </x-validation>
          @enderror
        </div>
    </div>
        <div class="grid grid-cols-1">
            <x-checkbox name="user.is_active"  model="user.is_active">{{ __('Is active?') }}</x-checkbox>
        </div>

      <x-modal-button>{{ __('Save user') }}</x-modal-button>
</div>
</div>
