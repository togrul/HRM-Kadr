@props([
  'node',
  'depth' => 0,
  'openIds' => [],
  'search' => '',
])

@php
    $agg = $node['agg'];
    $total = (int) $agg['total'];

    // Type chip derived from structure depth (level): müəssisə → departament → şöbə → vahid.
    [$typeLabel, $typeChip] = match (true) {
        $node['level'] <= 1 => [__('staff::common.structure_levels.enterprise'), 'bg-indigo-50 text-indigo-600'],
        $node['level'] === 2 => [__('staff::common.structure_levels.department'), 'bg-blue-50 text-blue-600'],
        $node['level'] === 3 => [__('staff::common.structure_levels.division'), 'bg-zinc-100 text-zinc-500'],
        default => [__('staff::common.structure_levels.unit'), 'bg-zinc-50 text-zinc-400 ring-1 ring-inset ring-zinc-200/70'],
    };

    $offStaff = $node['off_staff'] ?? [];
    $over = (int) ($agg['over'] ?? 0);
    $offStaffCount = (int) ($agg['off_staff'] ?? 0);
    $hasChildren = count($node['children']) > 0 || count($node['positions']) > 0 || count($offStaff) > 0;
    // Server-side: a closed branch is not in the DOM at all, so the page costs what is
    // on screen rather than the whole org chart.
    $isOpen = in_array((int) $node['id'], $openIds, true);
    $hasOwnPositions = count($node['positions']) > 0;
    $pad = $depth * 22;

    $canAdd = auth()->user()?->can('add-staff') ?? false;
    $canEdit = auth()->user()?->can('edit-staff') ?? false;
    $canDelete = auth()->user()?->can('delete-staff') ?? false;
@endphp

<div {{ $attributes }}>
    {{-- ── structure row ── --}}
    <div class="group hrm-tree-row">
        <div class="flex min-w-0 flex-1 items-center gap-2" style="padding-left: {{ $pad }}px">
            @if ($hasChildren)
                <button type="button" wire:click="toggleNode({{ $node['id'] }})" wire:loading.attr="disabled" wire:target="toggleNode" class="hrm-tree-toggle">
                    <svg @class(['h-4 w-4 transition-transform duration-200', '-rotate-90' => ! $isOpen])
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            @else
                <span class="h-5 w-5 shrink-0"></span>
            @endif

            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $typeChip }}">{{ $typeLabel }}</span>
            <span class="truncate text-[14px] font-semibold text-zinc-900"><x-staff.highlight :text="$node['name']" :query="$search" /></span>
            @if ($over > 0)
                <span class="shrink-0 rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('staff::common.fields.over_count', ['count' => $over]) }}</span>
            @endif
            @if ($offStaffCount > 0)
                <span class="shrink-0 rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('staff::common.fields.off_staff_count', ['count' => $offStaffCount]) }}</span>
            @endif
        </div>

        <x-staff.metric :value="$total" tone="total" :showLabel="false" />
        <x-staff.metric :value="$agg['filled']" tone="filled" :showLabel="false" />
        <x-staff.metric :value="$agg['vacant']" tone="vacant" :showLabel="false" />

        {{-- operations (revealed in edit mode) — add position on any node; edit/delete only
             where the structure has its own positions (avoids editing an empty container). --}}
        <div class="flex w-[108px] shrink-0 items-center justify-end gap-0.5" x-show="editMode" x-cloak>
            @if ($canAdd)
                <button type="button" wire:click="addStaffFor({{ $node['id'] }})"
                    wire:loading.attr="disabled" wire:target="addStaffFor"
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                    title="{{ __('staff::common.actions.add_staff') }}" aria-label="{{ __('staff::common.actions.add_staff') }}">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                </button>
            @endif
            @if ($canEdit && $hasOwnPositions)
                <button type="button" wire:click="openSideMenu('edit-staff',{{ $node['id'] }})"
                    wire:loading.attr="disabled" wire:target="openSideMenu"
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700"
                    title="{{ __('staff::common.titles.edit_staff') }}" aria-label="{{ __('staff::common.titles.edit_staff') }}">
                    <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </button>
            @endif
            @if ($canDelete && $hasOwnPositions)
                <button type="button" wire:click.prevent="setDeleteStaff({{ $node['id'] }})"
                    wire:loading.attr="disabled" wire:target="setDeleteStaff"
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-rose-400 transition-colors hover:bg-rose-50 hover:text-rose-500"
                    title="{{ __('staff::common.titles.delete_staff') }}" aria-label="{{ __('staff::common.titles.delete_staff') }}">
                    <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                </button>
            @endif
        </div>
    </div>

    {{-- ── collapsible body: positions then child structures ── --}}
    @if ($isOpen)
    <div>
        @foreach ($node['positions'] as $p)
            @php
                $unassigned = ($p['kind'] ?? 'row') === 'unassigned';
            @endphp
            <div @class([
                'flex items-center gap-3 border-b px-3 py-2 transition-colors',
                'border-zinc-50 hover:bg-zinc-50/70' => ! $unassigned,
                'border-amber-100 bg-amber-50/60 hover:bg-amber-50' => $unassigned,
            ])>
                <div class="flex min-w-0 flex-1 items-center gap-2" style="padding-left: {{ ($depth + 1) * 22 }}px">
                    <span class="h-5 w-5 shrink-0"></span>
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-zinc-50 text-zinc-400 ring-1 ring-inset ring-zinc-200/60">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    @if ($unassigned)
                        <span class="truncate text-[14px] font-medium text-amber-700" title="{{ __('staff::common.messages.position_unassigned_hint') }}">{{ $p['title'] }}</span>
                    @else
                        <span class="truncate text-[14px] text-zinc-700"><x-staff.highlight :text="$p['title']" :query="$search" /></span>
                    @endif
                    @if ((int) $p['vacant'] > 0)
                        <x-small-badge mode="rose">{{ __('staff::common.fields.vacant_lower') }}</x-small-badge>
                    @endif
                    @if ((int) ($p['over'] ?? 0) > 0)
                        <span class="shrink-0 rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-200">{{ __('staff::common.fields.over_count', ['count' => (int) $p['over']]) }}</span>
                    @endif
                </div>

                <x-staff.metric :value="$p['total']" tone="total" :showLabel="false" />

                @if ($unassigned)
                    <x-staff.metric :value="$p['filled']" tone="filled" :showLabel="false" />
                @else
                    <button type="button"
                        wire:click="openSideMenu('show-staff',{{ $p['structure_id'] }},{{ $p['position_id'] }})"
                        wire:loading.attr="disabled" wire:target="openSideMenu"
                        class="rounded-lg transition-colors hover:bg-zinc-100 disabled:cursor-default disabled:opacity-70"
                        title="{{ __('staff::common.fields.filled') }}">
                        <x-staff.metric :value="$p['filled']" tone="filled" :showLabel="false" />
                    </button>
                @endif

                <x-staff.metric :value="$p['vacant']" tone="vacant" :showLabel="false" />

                <span class="hidden w-[108px] shrink-0 items-center justify-end sm:flex" x-show="editMode" x-cloak>
                    @if ($unassigned && $canEdit)
                        <button type="button" wire:click="openSideMenu('edit-staff',{{ $p['structure_id'] }})"
                            wire:loading.attr="disabled" wire:target="openSideMenu"
                            class="rounded-lg px-2 py-1 text-[12px] font-medium text-amber-700 transition-colors hover:bg-amber-100">
                            {{ __('staff::common.actions.assign_position') }}
                        </button>
                    @endif
                </span>
            </div>
        @endforeach

        @if (count($offStaff) > 0)
            <div class="flex items-center gap-2 border-b border-amber-100 bg-amber-50/40 px-3 py-1.5" style="padding-left: {{ ($depth + 1) * 22 + 12 }}px">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">{{ __('staff::common.fields.off_staff') }}</span>
                <span class="truncate text-[11.5px] text-amber-700/80">{{ __('staff::common.messages.off_staff_hint') }}</span>
            </div>
            @foreach ($offStaff as $p)
                <div class="flex items-center gap-3 border-b border-amber-100/70 px-3 py-2 transition-colors hover:bg-amber-50/50">
                    <div class="flex min-w-0 flex-1 items-center gap-2" style="padding-left: {{ ($depth + 1) * 22 }}px">
                        <span class="h-5 w-5 shrink-0"></span>
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-amber-50 text-amber-500 ring-1 ring-inset ring-amber-200/70">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                        </span>
                        <span class="truncate text-[14px] text-zinc-700"><x-staff.highlight :text="$p['title']" :query="$search" /></span>
                    </div>

                    <x-staff.metric :value="0" tone="total" :showLabel="false" />

                    @if ((int) $p['position_id'] > 0)
                        <button type="button"
                            wire:click="openSideMenu('show-staff',{{ $p['structure_id'] }},{{ $p['position_id'] }})"
                            wire:loading.attr="disabled" wire:target="openSideMenu"
                            class="rounded-lg transition-colors hover:bg-zinc-100 disabled:cursor-default disabled:opacity-70"
                            title="{{ __('staff::common.fields.filled') }}">
                            <x-staff.metric :value="$p['filled']" tone="filled" :showLabel="false" />
                        </button>
                    @else
                        <x-staff.metric :value="$p['filled']" tone="filled" :showLabel="false" />
                    @endif

                    <x-staff.metric :value="0" tone="vacant" :showLabel="false" />

                    <span class="hidden w-[108px] shrink-0 sm:block" x-show="editMode" x-cloak aria-hidden="true"></span>
                </div>
            @endforeach
        @endif

        @foreach ($node['children'] as $child)
            <x-staff.tree-node wire:key="staff-node-{{ $child['id'] }}" :node="$child" :depth="$depth + 1" :open-ids="$openIds" :search="$search" />
        @endforeach
    </div>
    @endif
</div>
