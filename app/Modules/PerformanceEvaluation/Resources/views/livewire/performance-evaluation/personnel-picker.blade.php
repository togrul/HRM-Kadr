<div>
    <x-ui.search-input-select
        id="picker_{{ $this->getId() }}"
        searchModel="query"
        :selected="$selectedId ? ['id' => $selectedId, 'name' => $selectedLabel] : null"
        onClear="clear"
        :placeholder="$placeholder"
    >
        @foreach ($this->results as $res)
            <button type="button" wire:click="select({{ $res['id'] }}, @js($res['label']))" x-on:click="setOpen(false)"
                class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-zinc-100">{{ $res['label'] }}</button>
        @endforeach
    </x-ui.search-input-select>
</div>
