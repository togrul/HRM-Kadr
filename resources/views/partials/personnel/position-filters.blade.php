<div class="flex justify-start items-center flex-wrap gap-2">
    @foreach ($this->positions as $position)
        <button
            wire:click.prevent="setPosition({{ $position->id }})"
            wire:loading.attr="disabled"
            wire:target="setPosition"
            @class([
            'appearance-none w-max text-sm font-medium bg-gray-50 border rounded-md px-3 py-1 transition-all duration-300 hover:shadow-sm hover:text-gray-900',
            'shadow-none text-teal-500' => $position->id == $selectedPosition,
            'shadow-md text-gray-600' => $position->id != $selectedPosition,
        ])>
            {{ $position->name }}
        </button>
    @endforeach

    @if (!empty($selectedPosition))
        <button
            wire:click.prevent="resetFilter"
            wire:loading.attr="disabled"
            wire:target="resetFilter"
            class="appearance-none w-max text-sm font-medium bg-slate-100 text-rose-500 rounded-2xl px-3 py-1 transition-all duration-300 hover:bg-slate-200">
            {{ __('Reset') }}
        </button>
    @endif
</div>
