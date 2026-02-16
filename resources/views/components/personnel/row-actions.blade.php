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
    <div class="flex items-center space-x-2">
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
                    class="flex items-center justify-center w-9 h-9 text-xs font-medium text-gray-500 uppercase rounded-lg bg-gray-100 hover:bg-gray-200 hover:text-gray-700"
                    title="{{ $action->label }}"
                >
                    <x-dynamic-component :component="$action->icon" />
                </a>
            @else
                <button
                    wire:click="handleRowAction('{{ $action->id }}', @js($action->actionPayload))"
                    @if ($action->confirmMessage)
                        wire:confirm="{{ $action->confirmMessage }}"
                    @endif
                    @if ($action->wireTarget)
                        wire:loading.attr="disabled"
                        wire:target="{{ $action->wireTarget }}"
                    @else
                        wire:loading.attr="disabled"
                        wire:target="handleRowAction"
                    @endif
                    class="flex items-center justify-center text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg w-9 h-9 hover:bg-gray-200 hover:text-gray-700"
                    title="{{ $action->label }}"
                >
                    <x-dynamic-component
                        :component="$action->icon"
                        :color="$action->iconProps['color'] ?? null"
                        :hover="$action->iconProps['hover'] ?? null"
                    />
                </button>
            @endif
        @endforeach
    </div>
</x-table.td>
