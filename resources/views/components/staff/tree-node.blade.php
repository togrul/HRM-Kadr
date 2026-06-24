@props([
  'node',
  'depth' => 0,
])

@php
    $agg = $node['agg'];
    $total = (int) $agg['total'];
    $rate = (int) $agg['rate'];
    $hasCap = $total > 0;

    $tone = ! $hasCap ? 'muted' : ($rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger'));
    $barClass = ['success' => 'bg-emerald-500', 'warning' => 'bg-amber-500', 'danger' => 'bg-rose-500', 'muted' => 'bg-zinc-200'][$tone];
    $pillClass = [
        'success' => 'bg-emerald-50 text-emerald-600',
        'warning' => 'bg-amber-50 text-amber-600',
        'danger'  => 'bg-rose-50 text-rose-600',
        'muted'   => 'bg-zinc-100 text-zinc-400',
    ][$tone];

    // Type chip derived from structure depth (level): müəssisə → departament → şöbə → vahid.
    [$typeLabel, $typeChip] = match (true) {
        $node['level'] <= 1 => ['MÜƏSSİSƏ', 'bg-indigo-50 text-indigo-600'],
        $node['level'] === 2 => ['DEPARTAMENT', 'bg-blue-50 text-blue-600'],
        $node['level'] === 3 => ['ŞÖBƏ', 'bg-zinc-100 text-zinc-500'],
        default => ['VAHİD', 'bg-zinc-50 text-zinc-400 ring-1 ring-inset ring-zinc-200/70'],
    };

    $hasChildren = count($node['children']) > 0 || count($node['positions']) > 0;
    $hasOwnPositions = count($node['positions']) > 0;
    $pad = $depth * 22;

    $canAdd = auth()->user()?->can('add-staff') ?? false;
    $canEdit = auth()->user()?->can('edit-staff') ?? false;
    $canDelete = auth()->user()?->can('delete-staff') ?? false;
@endphp

<div {{ $attributes }}>
    {{-- ── structure row ── --}}
    <div class="group flex items-center gap-3 border-b border-zinc-100 px-3 py-2.5 transition-colors hover:bg-zinc-50/70">
        <div class="flex min-w-0 flex-1 items-center gap-2" style="padding-left: {{ $pad }}px">
            @if ($hasChildren)
                <button type="button" x-on:click="toggle({{ $node['id'] }})"
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-200/60 hover:text-zinc-600">
                    <svg class="h-4 w-4 transition-transform duration-200" :class="isOpen({{ $node['id'] }}) ? 'rotate-0' : '-rotate-90'"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            @else
                <span class="h-5 w-5 shrink-0"></span>
            @endif

            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $typeChip }}">{{ $typeLabel }}</span>
            <span class="truncate text-[14px] font-semibold text-zinc-900">{{ $node['name'] }}</span>
        </div>

        <div class="hidden w-28 shrink-0 items-center gap-2 sm:flex">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100">
                <div class="h-full rounded-full {{ $barClass }} transition-all" style="width: {{ $hasCap ? max(3, $rate) : 0 }}%"></div>
            </div>
        </div>

        <x-staff.metric :value="$total" tone="total" :showLabel="false" />
        <x-staff.metric :value="$agg['filled']" tone="filled" :showLabel="false" />
        <x-staff.metric :value="$agg['vacant']" tone="vacant" :showLabel="false" />

        <div class="flex w-12 shrink-0 justify-center">
            <span class="rounded-md px-1.5 py-0.5 text-[12px] font-semibold tabular-nums {{ $pillClass }}">{{ $hasCap ? $rate.'%' : '—' }}</span>
        </div>

        {{-- operations (revealed in edit mode) — add position on any node; edit/delete only
             where the structure has its own positions (avoids editing an empty container). --}}
        <div class="flex w-[108px] shrink-0 items-center justify-end gap-0.5" x-show="editMode" x-cloak>
            @if ($canAdd)
                <button type="button" wire:click="addStaffFor({{ $node['id'] }})"
                    wire:loading.attr="disabled" wire:target="addStaffFor"
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                    title="{{ __('staff::common.actions.add_staff') }}">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                </button>
            @endif
            @if ($canEdit && $hasOwnPositions)
                <button type="button" wire:click="openSideMenu('edit-staff',{{ $node['id'] }})"
                    wire:loading.attr="disabled" wire:target="openSideMenu"
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700"
                    title="{{ __('staff::common.titles.edit_staff') }}">
                    <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </button>
            @endif
            @if ($canDelete && $hasOwnPositions)
                <button type="button" wire:click.prevent="setDeleteStaff({{ $node['id'] }})"
                    wire:loading.attr="disabled" wire:target="setDeleteStaff"
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-rose-400 transition-colors hover:bg-rose-50 hover:text-rose-500"
                    title="{{ __('staff::common.titles.delete_staff') }}">
                    <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                </button>
            @endif
        </div>
    </div>

    {{-- ── collapsible body: positions then child structures ── --}}
    <div x-show="isOpen({{ $node['id'] }})" x-collapse>
        @foreach ($node['positions'] as $p)
            @php
                $pTotal = (int) $p['total'];
                $pRate = $pTotal > 0 ? (int) round($p['filled'] / $pTotal * 100) : 0;
                $pCap = $pTotal > 0;
                $pTone = ! $pCap ? 'muted' : ($pRate >= 80 ? 'success' : ($pRate >= 50 ? 'warning' : 'danger'));
                $pBar = ['success' => 'bg-emerald-500', 'warning' => 'bg-amber-500', 'danger' => 'bg-rose-500', 'muted' => 'bg-zinc-200'][$pTone];
                $pPill = [
                    'success' => 'bg-emerald-50 text-emerald-600',
                    'warning' => 'bg-amber-50 text-amber-600',
                    'danger'  => 'bg-rose-50 text-rose-600',
                    'muted'   => 'bg-zinc-100 text-zinc-400',
                ][$pTone];
            @endphp
            <div class="flex items-center gap-3 border-b border-zinc-50 px-3 py-2 transition-colors hover:bg-zinc-50/70">
                <div class="flex min-w-0 flex-1 items-center gap-2" style="padding-left: {{ ($depth + 1) * 22 }}px">
                    <span class="h-5 w-5 shrink-0"></span>
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-zinc-50 text-zinc-400 ring-1 ring-inset ring-zinc-200/60">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span class="truncate text-[14px] text-zinc-700">{{ $p['title'] }}</span>
                </div>

                <div class="hidden w-28 shrink-0 items-center gap-2 sm:flex">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100">
                        <div class="h-full rounded-full {{ $pBar }} transition-all" style="width: {{ $pCap ? max(3, $pRate) : 0 }}%"></div>
                    </div>
                </div>

                <x-staff.metric :value="$p['total']" tone="total" :showLabel="false" />

                <button type="button"
                    wire:click="openSideMenu('show-staff',{{ $p['structure_id'] }},{{ $p['position_id'] }})"
                    wire:loading.attr="disabled" wire:target="openSideMenu"
                    class="rounded-lg transition-colors hover:bg-zinc-100 disabled:cursor-default disabled:opacity-70"
                    title="{{ __('staff::common.fields.filled') }}">
                    <x-staff.metric :value="$p['filled']" tone="filled" :showLabel="false" />
                </button>

                <x-staff.metric :value="$p['vacant']" tone="vacant" :showLabel="false" />

                <div class="flex w-12 shrink-0 justify-center">
                    <span class="rounded-md px-1.5 py-0.5 text-[12px] font-semibold tabular-nums {{ $pPill }}">{{ $pCap ? $pRate.'%' : '—' }}</span>
                </div>

                <span class="hidden w-[108px] shrink-0 sm:block" x-show="editMode" x-cloak aria-hidden="true"></span>
            </div>
        @endforeach

        @foreach ($node['children'] as $child)
            <x-staff.tree-node wire:key="staff-node-{{ $child['id'] }}" :node="$child" :depth="$depth + 1" />
        @endforeach
    </div>
</div>
