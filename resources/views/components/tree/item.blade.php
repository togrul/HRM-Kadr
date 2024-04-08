@props([
    'model'
])
<li class="py-1" x-data="{openSub:true}">
    <a class="flex items-center space-x-2">
        @if(count($model->subs) > 0)
        <button @click="openSub = !openSub" class="rounded-lg bg-blue-100 text-blue-500 p-1 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path x-show="openSub"
                    stroke-linecap="round"
                    stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90"
                />
                <path
                    x-show="!openSub"
                    stroke-linecap="round"
                    stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90"
                />
              </svg>
        </button>
        @else
        <span class="w-7 h-7"></span>
        @endif
        <button
            wire:click.prevent="selectStructure({{ $model->id }})"
            @class([
                'font-medium appearance-none transition-all duration-300 text-left',
                'text-blue-500' => $model->id == $this->selectedStructure
            ])
            >
            {{ $slot }}
        </button>
    </a>
    @if(count($model->subs) > 0)
    <ul class="ml-4 flex-col flex" x-show="openSub">
        @foreach ($model->subs as $sub)
             <x-tree.item :model="$sub"> - {{ $sub->name }}</x-tree.item>
        @endforeach
    </ul>
    @endif
</li>
