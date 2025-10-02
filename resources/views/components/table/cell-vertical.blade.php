@props(['title', 'textColor' => 'text-zinc-900'])

<div class="flex flex-col space-y-1">
    <span class="text-zinc-500 text-sm font-medium border-b border-dashed border-zinc-500">{{ __($title) }}</span>
    <span class="{{$textColor}} text-sm font-medium">{{ $slot }}</span>
</div>
