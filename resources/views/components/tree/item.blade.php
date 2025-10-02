@props(['model', 'level' => 0])

@php
    $hasSubs = $model->subs->isNotEmpty();
    $isSelected = $model->id === $this->selectedStructure;
@endphp

<li
    class="relative py-1 overflow-hidden"
    x-data="{ openSub: true }"
>
    @if($hasSubs)
        <span class="absolute top-8 left-2.5 w-px h-full bg-neutral-300" x-show="openSub" x-cloak></span>
    @else
        <span class="absolute top-[14px] left-0 flex items-center">
            <span class="h-px w-5 bg-neutral-300"></span>
            <span class="h-1.5 w-1.5 top-[2px] rounded-full bg-neutral-500"></span>
        </span>
    @endif
        <div class="flex flex-col">
            <div class="flex items-center gap-2">
                <div class="flex flex-none items-center">
                    @if($hasSubs)
                        <button
                            type="button"
                            @click="openSub = !openSub"
                            @keydown.enter.prevent="openSub = !openSub"
                            @keydown.space.prevent="openSub = !openSub"
                            :aria-expanded="openSub.toString()"
                            aria-controls="subs-{{ $model->id }}"
                            class="rounded focus:outline-none"
                        >
                            @include('components.icons.chevron-right-icon', [
                                'show'  => '!openSub',
                                'size'  => 'w-5 h-5',
                                'color' => 'text-slate-500',
                                'hover' => 'text-slate-600'
                            ])
                            @include('components.icons.chevron-down-icon', [
                                'show'  => 'openSub',
                                'size'  => 'w-5 h-5',
                                'color' => 'text-slate-500',
                                'hover' => 'text-slate-600'
                            ])
                        </button>
                    @else
                        <span class="w-7 h-7"></span>
                    @endif
            </div>
            <div class="flex-1 min-w-0">
                <button
                    type="button"
                    wire:click.prevent="selectStructure({{ $model->id }})"
                    wire:key="node-{{ $model->id }}"
                    @class([
                        'font-medium appearance-none transition-colors duration-200 text-left focus:outline-none',
                        'text-blue-500' => $isSelected,
                        'text-gray-600' => !$isSelected,
                    ])
                >
                    {{ $slot }}
                </button>
            </div>
        </div>
            @if($hasSubs)
                <ul
                    id="subs-{{ $model->id }}"
                    class="ml-[13px] flex flex-col"
                    x-show="openSub"
                    x-collapse
                    x-cloak
                >
                    @foreach ($model->subs as $sub)
                        <x-tree.item :model="$sub" :level="$level + 1" wire:key="node-{{ $sub->id }}">
                            {{ $sub->name }}
                        </x-tree.item>
                    @endforeach
                </ul>
            @endif
    </div>
</li>
