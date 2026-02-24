@props(['hasParent', 'model'])

<div @class([
    'grid grid-cols-1 w-full items-center',
    'gap-2 md:grid-cols-6 bg-white rounded-lg shadow-sm px-3 py-2' => $hasParent,
])>
  <div @class([
    'md:col-span-4' => $hasParent
  ])>
      <p class="text-base">{{ $model->position?->name }}</p>
  </div>
  <div 
      @class([
        'flex items-center gap-8 w-full',
        'md:col-span-2 justify-end' => $hasParent,
        'bg-white rounded-lg shadow-sm px-3 py-2 grid grid-cols-3' => !$hasParent
      ])
  >
    <div class="flex flex-col space-y-1 items-center">
        <p class="text-sm font-medium text-zinc-500">
            {{ __('Total') }}
          </p>
        <p class="text-blue-600/90 font-semibold bg-blue-100/40 rounded-md w-7 h-7 flex items-center justify-center">{{ $model->total }}</p>
    </div>
    <div  
      class="flex flex-col space-y-1 items-center cursor-pointer"
      @if ($hasParent) wire:click="openSideMenu('show-staff',{{ $model->structure_id }},{{ $model->position_id }})" @endif
    >
      <p class="text-sm font-medium text-zinc-500">
          {{ __('Filled') }}
        </p>
        <p class="text-rose-600/90 font-semibold bg-rose-100/40 rounded-md w-7 h-7 flex items-center justify-center">{{ $model->filled }}</p>
      </div>
      <div class="flex flex-col space-y-1 items-center">
        <p class="text-sm font-medium text-zinc-500">
            {{ __('Vacant') }}
          </p>
        <p class="text-green-600/90 font-semibold bg-green-100/40 rounded-md w-7 h-7 flex items-center justify-center">{{ $model->vacant }}</p>
      </div>
  </div>
</div>
