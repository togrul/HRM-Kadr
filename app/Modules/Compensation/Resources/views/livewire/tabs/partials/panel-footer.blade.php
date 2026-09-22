<div class="flex items-center justify-end gap-2 border-t border-hairline-subtle bg-white px-5 py-3.5">
    <x-pill-button x-on:click="close()">{{ __('compensation::dashboard.actions.cancel') }}</x-pill-button>
    <x-pill-button variant="primary" wire:click="{{ $save }}">{{ __('compensation::dashboard.actions.save') }}</x-pill-button>
</div>
