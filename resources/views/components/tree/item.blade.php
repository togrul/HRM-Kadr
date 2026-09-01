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
            <div class="flex h-5 w-4 flex-none items-center justify-center">
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
                        <x-icons.chevron-right-icon show="!openSub" size="w-3.5 h-3.5" color="text-ink-faint" hover="text-ink-muted"></x-icons.chevron-right-icon>
                        <x-icons.chevron-down-icon show="openSub" size="w-3.5 h-3.5" color="text-ink-faint" hover="text-ink-muted"></x-icons.chevron-down-icon>
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
                        'relative flex w-full items-start gap-1.5 rounded-lg py-1 pr-2 text-left text-[14px] leading-snug transition-colors duration-150 focus:outline-none',
                        // The selected unit is the active filter, so it needs to read as a state,
                        // not as the hover it otherwise looks identical to.
                        'bg-[#f4f4f5] pl-3 font-semibold text-ink' => $isSelected,
                        'pl-2 font-medium text-ink-soft hover:bg-[#fafafa]' => ! $isSelected && $isRoot,
                        'pl-2 font-normal text-ink-muted hover:bg-[#fafafa]' => ! $isSelected && ! $isRoot,
                    ])
                    @if ($isSelected) aria-current="true" @endif
                >
                    @if ($isSelected)
                        <span class="absolute left-0 top-1/2 h-[18px] w-[3px] -translate-y-1/2 rounded-full bg-ink" aria-hidden="true"></span>
                    @endif

                    @if ($isRoot)
                        <svg class="mt-px h-4 w-4 shrink-0 text-ink-faint" viewBox="0 0 24 24" stroke-width="1.5" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M10 18V15C10 13.8954 10.8954 13 12 13V13C13.1046 13 14 13.8954 14 15V18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                          <path d="M2 8L11.7317 3.13416C11.9006 3.04971 12.0994 3.0497 12.2683 3.13416L22 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                          <path d="M20 11V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    @endif
                    <span class="block min-w-0 break-words">{{ $slot }}</span>
                </button>
            </div>
        </div>

        @if($hasSubs)
            <ul
                id="subs-{{ $model->id }}"
                class="relative ml-[.55rem] flex flex-col border-l border-hairline pl-1"
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
