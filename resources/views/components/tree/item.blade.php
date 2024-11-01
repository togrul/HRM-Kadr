@props(['model'])

<li class="py-1" x-data="{openSub:true}">
    <a class="flex items-center space-x-2">
        @if($model->subs->isNotEmpty())
            <button @click="openSub = !openSub" class="rounded-lg bg-white text-blue-500 p-1 shadow-sm">
                @include('components.icons.add-icon',['show' => '!openSub' , 'size' => 'w-5 h-5', 'color' => 'text-slate-500','hover' => 'text-slate-600'])
                @include('components.icons.minus-icon',['show' => 'openSub', 'size' => 'w-5 h-5', 'color' => 'text-slate-500','hover' => 'text-slate-600'])
            </button>
        @else
            <span class="w-7 h-7"></span>
        @endif
        <button
            wire:click.prevent="selectStructure({{ $model->id }})"
            @class([
                'font-medium appearance-none transition-all duration-300 text-left',
                'text-blue-500' => $model->id == $this->selectedStructure,
                'text-gray-600' => $model->id!= $this->selectedStructure,
            ])
            >
            {{ $slot }}
        </button>
    </a>
    @if($model->subs->isNotEmpty())
        <ul class="ml-4 flex-col flex" x-show="openSub">
            @foreach ($model->subs as $sub)
                 <x-tree.item :model="$sub"> - {{ $sub->name }}</x-tree.item>
            @endforeach
        </ul>
    @endif
</li>
