<x-filter.scroller :label="__('personnel::common.labels.position')">
    <x-filter.nav>
        @foreach ($this->positions as $position)
            <x-filter.item
                wire:click.prevent="setPosition({{ $position->id }})"
                wire:loading.attr="disabled"
                wire:target="setPosition"
                :active="$position->id == $selectedPosition"
            >{{ $position->name }}</x-filter.item>
        @endforeach

        @if (! empty($selectedPosition))
            <li class="shrink-0">
                <button
                    type="button"
                    wire:click.prevent="resetFilter"
                    wire:loading.attr="disabled"
                    wire:target="resetFilter"
                    class="flex min-h-9 items-center whitespace-nowrap rounded-[9px] px-3 text-[14px] font-medium text-[#be123c] transition hover:bg-[#ffe4e6] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                >{{ __('personnel::common.actions.reset') }}</button>
            </li>
        @endif
    </x-filter.nav>
</x-filter.scroller>
