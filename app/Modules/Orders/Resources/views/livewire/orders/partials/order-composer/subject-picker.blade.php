{{-- Search-and-pick field for the order's subject (employee or candidate).
     Expects: $query (search property), $selected (picked label), $results, $select /
     $clear (component actions), $error (error bag key), $label, $placeholder. --}}
<div class="relative">
    <x-label for="{{ $query }}">{{ $label }}</x-label>
    @if ($selected)
        <div class="flex items-center justify-between px-3 py-2 mt-1 text-sm rounded-lg bg-neutral-100">
            <span class="font-medium text-zinc-900">{{ $selected }}</span>
            <button type="button" wire:click="{{ $clear }}" class="text-zinc-400 hover:text-red-600">✕</button>
        </div>
    @else
        <x-livewire-input mode="gray" name="{{ $query }}"
            wire:model.live.debounce.300ms="{{ $query }}"
            placeholder="{{ $placeholder }}" />
        @if (count($results) > 0)
            <div class="absolute z-20 w-full mt-1 overflow-auto bg-white border shadow-lg max-h-56 rounded-lg border-zinc-200">
                @foreach ($results as $r)
                    <button type="button" wire:click="{{ $select }}({{ $r['id'] }})"
                        class="block w-full px-3 py-2 text-sm text-left hover:bg-zinc-100">{{ $r['label'] }}</button>
                @endforeach
            </div>
        @endif
    @endif
    @error($error) <x-validation>{{ $message }}</x-validation> @enderror
</div>
