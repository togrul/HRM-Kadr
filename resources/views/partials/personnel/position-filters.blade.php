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
                    class="flex h-[30px] items-center whitespace-nowrap rounded-[9px] px-2.5 text-[12px] font-medium text-[#be123c] transition hover:bg-[#ffe4e6]"
                >{{ __('personnel::common.actions.reset') }}</button>
            </li>
        @endif
    </x-filter.nav>
</x-filter.scroller>
