@props(['filters'])

<button @click="$wire.dispatch('setOpenFilter')" @class([
    'flex relative items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-gray-100',
    'bg-gray-100' => count($filters) > 0,
]) type="button" title="Filter">
    <x-icons.search-file />
    @if (count($filters) > 0)
        <span class="absolute top-0 right-0 rounded-full bg-rose-500 text-white flex justify-center w-4 h-4 text-xs">
            {{ count($filters) }}
        </span>
    @endif
</button>
