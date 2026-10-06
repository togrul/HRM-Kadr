@props(['model', 'level' => 0])

{{--
    One structure node. Selection and fold state live in the sidebar root's x-data
    (pick / sel / isOpen / toggle), so a node carries no Alpine scope of its own and a
    click never round-trips just to move the highlight. The server still renders the
    initial aria-current so the first paint is right before Alpine boots. Styling is the
    .hrm-structure-* classes in app.css — this markup repeats once per org unit.
--}}
@php
    $id = $model->id;
    $hasSubs = $model->subs->isNotEmpty();
    $isRoot = $level === 0;
@endphp

<li class="relative overflow-hidden">
<div class="flex items-center gap-0.5">
@if ($hasSubs)
<button type="button" class="hrm-structure-toggle" x-on:click="toggle({{ $id }})" :aria-expanded="isOpen({{ $id }})" aria-controls="subs-{{ $id }}"><svg class="h-3.5 w-3.5 transition-transform duration-200" :class="isOpen({{ $id }}) ? '' : '-rotate-90'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></button>
@else
<span class="w-4 flex-none"></span>
@endif
<button type="button" @class(['hrm-structure-node', 'font-medium text-ink-soft' => $isRoot, 'font-normal text-ink-muted' => ! $isRoot]) x-on:click="pick({{ $id }})" :aria-current="sel({{ $id }})" @if ($id === $this->selectedStructure) aria-current="true" @endif>
@if ($isRoot)
<svg class="mt-px h-4 w-4 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 18V15C10 13.8954 10.8954 13 12 13V13C13.1046 13 14 13.8954 14 15V18"/><path d="M2 8L11.7317 3.13416C11.9006 3.04971 12.0994 3.0497 12.2683 3.13416L22 8"/><path d="M20 11V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V11"/></svg>
@endif
<span class="block min-w-0 break-words">{{ $slot }}</span>
</button>
</div>
@if ($hasSubs)
<ul id="subs-{{ $id }}" class="hrm-structure-subs" x-show="isOpen({{ $id }})" x-collapse x-cloak>
@foreach ($model->subs as $sub)
<x-tree.item :model="$sub" :level="$level + 1">{{ $sub->name }}</x-tree.item>
@endforeach
</ul>
@endif
</li>
