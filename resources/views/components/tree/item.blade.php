@props(['model', 'level' => 0])

@php
    $hasSubs = $model->subs->isNotEmpty();
    $isSelected = $model->id === $this->selectedStructure;
    $isRoot = $level === 0;
@endphp

<li
    class="relative overflow-hidden py-0"
    x-data="{ openSub: true }"
>
    <div class="flex flex-col">
        <div class="flex items-center gap-0.5">
            <div class="flex h-6 w-5 flex-none items-center justify-center">
                @if($hasSubs)
                    <button
                        type="button"
                        @click="openSub = !openSub"
                        x-on:keydown.enter.prevent="openSub = !openSub"
                        x-on:keydown.space.prevent="openSub = !openSub"
                        :aria-expanded="openSub.toString()"
                        aria-controls="subs-{{ $model->id }}"
                        class="rounded text-zinc-400 transition-colors hover:text-zinc-600 focus:outline-none"
                    >
                        @include('components.icons.chevron-right-icon', [
                            'show'  => '!openSub',
                            'size'  => 'w-4 h-4',
                            'color' => 'text-zinc-400',
                            'hover' => 'text-zinc-600'
                        ])
                        @include('components.icons.chevron-down-icon', [
                            'show'  => 'openSub',
                            'size'  => 'w-4 h-4',
                            'color' => 'text-zinc-400',
                            'hover' => 'text-zinc-600'
                        ])
                    </button>
                @else
                    <span class="h-3 w-3"></span>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <button
                    type="button"
                    wire:click.prevent="selectStructure({{ $model->id }})"
                    wire:key="node-{{ $model->id }}"
                    @class([
                        'flex w-full items-start gap-1.5 rounded-lg px-2 py-1 text-left transition-colors duration-150 focus:outline-none',
                        'bg-blue-50 font-medium text-blue-600' => $isSelected,
                        'font-medium text-zinc-900 hover:bg-zinc-100/80' => ! $isSelected && $isRoot,
                        'font-medium text-zinc-500 hover:bg-zinc-100/80' => ! $isSelected && ! $isRoot,
                    ])
                >
                    @if ($isRoot)
                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M3.4 6.6a.75.75 0 0 1 .38-.66l6-3.4a.75.75 0 0 1 .74 0l6 3.4A.75.75 0 0 1 16.8 7H3.2a.75.75 0 0 1-.74-.4ZM4.5 8.5h1.6v6.1H4.5V8.5Zm3.1 0h1.6v6.1H7.6V8.5Zm3.1 0h1.6v6.1h-1.6V8.5Zm3.1 0h1.6v6.1h-1.6V8.5ZM3 15.6h14a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1 0-1.5Z"/>
                        </svg>
                    @endif
                    <span class="block min-w-0 break-words leading-5">{{ $slot }}</span>
                </button>
            </div>
        </div>

        @if($hasSubs)
            <ul
                id="subs-{{ $model->id }}"
                class="relative ml-4 flex flex-col border-l border-zinc-200 pl-1"
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
