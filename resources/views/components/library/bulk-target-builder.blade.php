@props([
    'translationNs',
    'payload',
    'selectedStructureIds' => [],
    'selectedPositionIds' => [],
    'selectedPersonnelIds' => [],
    'assignmentForm' => [],
])

<div class="mt-4 space-y-4">
    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-[24px] bg-[#f5f5f7] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_10px_22px_rgba(0,0,0,0.035)]">
            <div class="flex items-center justify-between gap-3">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __($translationNs.'.sections.target_structures') }}</x-ui.field-label>
                <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-white px-2 py-1 text-[11px] font-semibold tracking-tight text-zinc-500">{{ count($selectedStructureIds) }}</span>
            </div>
            <div class="mt-3">
                <x-ui.filter-input wire:model.live.debounce.300ms="searchStructure" type="text" placeholder="{{ __($translationNs.'.messages.search_structure_placeholder') }}" />
            </div>
            @if ($payload['structures'] === [])
                <p class="mt-4 text-sm text-zinc-500">{{ __($translationNs.'.messages.empty_structures') }}</p>
            @else
                <div class="mt-4 grid max-h-[18rem] gap-3 overflow-y-auto pr-1">
                    @foreach ($payload['structures'] as $structure)
                        @php
                            $isSelected = in_array($structure['id'], $selectedStructureIds, true);
                        @endphp
                        <label @class([
                            'flex cursor-pointer items-start gap-3 rounded-2xl bg-white px-4 py-3 shadow-sm transition',
                            'ring-1 ring-zinc-300' => $isSelected,
                        ])>
                            <input type="checkbox" wire:click="toggleStructure({{ $structure['id'] }})" @checked($isSelected) class="library-target-checkbox mt-1" />
                            <div class="min-w-0">
                                <p class="break-words text-sm font-semibold tracking-tight text-zinc-950">{{ $structure['name'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-[24px] bg-[#f5f5f7] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_10px_22px_rgba(0,0,0,0.035)]">
            <div class="flex items-center justify-between gap-3">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __($translationNs.'.fields.target_positions') }}</x-ui.field-label>
                <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-white px-2 py-1 text-[11px] font-semibold tracking-tight text-zinc-500">{{ count($selectedPositionIds) }}</span>
            </div>
            <div class="mt-3">
                <x-ui.filter-input wire:model.live.debounce.300ms="searchPosition" type="text" placeholder="{{ __($translationNs.'.messages.search_position_placeholder') }}" />
            </div>
            @if ($payload['positions'] === [])
                <p class="mt-4 text-sm text-zinc-500">{{ __($translationNs.'.messages.empty_positions') }}</p>
            @else
                <div class="mt-4 grid max-h-[18rem] gap-3 overflow-y-auto pr-1">
                    @foreach ($payload['positions'] as $position)
                        @php
                            $isSelected = in_array($position['id'], $selectedPositionIds, true);
                        @endphp
                        <label @class([
                            'flex cursor-pointer items-start gap-3 rounded-2xl bg-white px-4 py-3 shadow-sm transition',
                            'ring-1 ring-zinc-300' => $isSelected,
                        ])>
                            <input type="checkbox" wire:click="togglePosition({{ $position['id'] }})" @checked($isSelected) class="library-target-checkbox mt-1" />
                            <div class="min-w-0">
                                <p class="break-words text-sm font-semibold tracking-tight text-zinc-950">{{ $position['name'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="rounded-[24px] bg-[#f5f5f7] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_10px_22px_rgba(0,0,0,0.035)]">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __($translationNs.'.sections.target_people') }}</x-ui.field-label>
                <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-white px-2 py-1 text-[11px] font-semibold tracking-tight text-zinc-500">{{ count($selectedPersonnelIds) }}</span>
            </div>
            <button type="button" wire:click="clearSelection" class="inline-flex items-center justify-center self-start rounded-full bg-white px-3 py-1.5 text-xs font-semibold tracking-tight text-zinc-600 shadow-sm transition hover:text-zinc-950">{{ __($translationNs.'.actions.clear_selection') }}</button>
        </div>
        <div class="mt-3">
            <x-ui.filter-input wire:model.live.debounce.300ms="searchPersonnel" type="text" placeholder="{{ __($translationNs.'.messages.search_personnel_placeholder') }}" />
        </div>
        @if ($payload['personnels'] === [])
            <p class="mt-4 text-sm text-zinc-500">{{ __($translationNs.'.messages.empty_personnels') }}</p>
        @else
            <div class="mt-4 grid max-h-[24rem] gap-3 overflow-y-auto pr-1 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($payload['personnels'] as $personnel)
                    @php
                        $isSelected = in_array($personnel['id'], $selectedPersonnelIds, true);
                    @endphp
                    <label @class([
                        'flex min-w-0 cursor-pointer items-start gap-3 rounded-2xl bg-white px-4 py-3 shadow-sm transition',
                        'ring-1 ring-zinc-300' => $isSelected,
                    ])>
                        <input type="checkbox" wire:click="togglePersonnel({{ $personnel['id'] }})" @checked($isSelected) class="library-target-checkbox mt-1" />
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ $personnel['fullname'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-zinc-600">{{ $personnel['tabel_no'] }} · {{ $personnel['position'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-zinc-500">{{ $personnel['structure'] }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    @if ($errors->has('selectedPersonnelIds'))
        <x-validation>{{ $errors->first('selectedPersonnelIds') }}</x-validation>
    @endif

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(20rem,0.9fr)]">
        <div class="rounded-[24px] bg-[#f5f5f7] px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_10px_22px_rgba(0,0,0,0.035)]">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __($translationNs.'.sections.targeting_rules') }}</x-ui.field-label>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ __($translationNs.'.fields.target_structures') }}: {{ count($selectedStructureIds) }}</span>
                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ __($translationNs.'.fields.target_positions') }}: {{ count($selectedPositionIds) }}</span>
                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ __($translationNs.'.fields.target_people') }}: {{ count($selectedPersonnelIds) }}</span>
                @if ($assignmentForm['include_recent_hires'])
                    <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700">{{ __($translationNs.'.fields.recent_hire_days') }}: {{ $assignmentForm['recent_hire_days'] }}</span>
                @endif
            </div>
        </div>

        <div class="rounded-[24px] bg-[#f5f5f7] px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_10px_22px_rgba(0,0,0,0.035)]">
            <div class="space-y-4">
                <div class="space-y-2">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __($translationNs.'.fields.include_recent_hires') }}</x-ui.field-label>
                    @php
                        $includeRecentHires = (bool) ($assignmentForm['include_recent_hires'] ?? false);
                    @endphp
                    <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                        <input wire:model.live="assignmentForm.include_recent_hires" type="checkbox" @checked($includeRecentHires) class="peer sr-only" />
                        <span @class([
                            'flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-zinc-400 peer-focus-visible:ring-offset-2',
                            'border-zinc-900 bg-zinc-900 text-white' => $includeRecentHires,
                            'border-zinc-300 bg-white text-transparent' => ! $includeRecentHires,
                        ])>
                            @if ($includeRecentHires)
                                <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M3.75 8.25 6.75 11.25 12.25 4.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            @endif
                        </span>
                        {{ __($translationNs.'.fields.include_recent_hires') }}
                    </label>
                </div>
                <x-ui.input-shell :label="__($translationNs.'.fields.recent_hire_days')" :error="$errors->first('assignmentForm.recent_hire_days')" labelClass="tracking-tight text-zinc-500">
                    <x-ui.filter-input wire:model.live="assignmentForm.recent_hire_days" type="number" min="1" max="365" />
                </x-ui.input-shell>
            </div>
        </div>
    </div>
</div>
