@props([
    'actions' => [],
    'status' => null,
    'forceUp' => false,
])

@php
    $inlineActions = collect($actions)
        ->filter(fn ($action) => ! $action->inMenu)
        ->values()
        ->all();

    $menuActions = collect($actions)
        ->filter(fn ($action) => $action->inMenu)
        ->values()
        ->all();
@endphp

<x-table.td :isButton="true" style="text-align: center !important;">
    <div class="flex items-center space-x-1.5">
        @if (! empty($menuActions))
            <x-personnel.row-actions.context-menu
                :$menuActions
                :force-up="$forceUp"
            />
        @endif

        @foreach ($inlineActions as $action)
            @if ($action->type === 'link')
                <a
                    href="{{ $action->href }}"
                    @if ($action->targetBlank)
                        target="_blank"
                        rel="noopener noreferrer"
                    @endif
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-ink-faint transition-colors hover:bg-[#f4f4f5] hover:text-ink"
                    title="{{ $action->label }}"
                >
                    <x-dynamic-component :component="$action->icon" color="text-current" hover="text-current" />
                </a>
            @else
                <button
                    @if ($action->confirmMessage)
                        x-on:click="$dispatch('confirm-action', { title: @js($action->label), message: @js($action->confirmMessage), confirmText: @js($action->label), tone: 'rose', run: () => $wire.handleRowAction(@js($action->id), @js($action->actionPayload)) })"
                    @else
                        wire:click="handleRowAction('{{ $action->id }}', @js($action->actionPayload))"
                    @endif
                    @if ($action->wireTarget)
                        wire:loading.attr="disabled"
                        wire:target="{{ $action->wireTarget }}"
                    @else
                        wire:loading.attr="disabled"
                        wire:target="handleRowAction"
                    @endif
                    {{-- quiet at rest; a destructive action (it asks for confirmation) only turns red on hover --}}
                    @class([
                        'inline-flex h-8 w-8 items-center justify-center rounded-md text-ink-faint transition-colors',
                        'hover:bg-[#ffe4e6] hover:text-[#be123c]' => $action->confirmMessage,
                        'hover:bg-[#f4f4f5] hover:text-ink' => ! $action->confirmMessage,
                    ])
                    title="{{ $action->label }}"
                >
                    <x-dynamic-component :component="$action->icon" color="text-current" hover="text-current" />
                </button>
            @endif
        @endforeach
    </div>
</x-table.td>
