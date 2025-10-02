@props(['hasParent', 'model'])

<div @class([
    'flex flex-col space-y-1 w-full',
    'bg-white/50 rounded-lg shadow-sm px-3 py-2' => $hasParent,
])>
    @if ($hasParent)
        <div class="px-3 py-2 w-max">
            <p class="text-base">{{ $model->position->name }}</p>
        </div>
    @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 items-center w-full">
        <div class="flex flex-col space-y-2 px-3 py-2 bg-white rounded-lg shadow-sm">
            <p class="text-sm font-medium text-zinc-500">
                {{ __('Total') }}</p>
            <p class="text-blue-600/90 font-medium">{{ $model->total }}</p>
        </div>
        <div @if ($hasParent) wire:click="openSideMenu('show-staff',{{ $model->structure_id }},{{ $model->position_id }})" @endif
            class="flex flex-col cursor-pointer space-y-2 px-3 py-2 bg-white rounded-lg shadow-sm">
            <p class="text-sm font-medium text-zinc-500">
                {{ __('Filled') }}</p>
            <p class="text-rose-600/90 font-medium">{{ $model->filled }}</p>
        </div>
        <div class="flex flex-col space-y-2 px-3 py-2 bg-white rounded-lg shadow-sm">
            <p class="text-sm font-medium text-zinc-500">
                {{ __('Vacant') }}</p>
            <p class="text-green-600/90 font-medium">{{ $model->vacant }}</p>
        </div>
    </div>
</div>
