@php
    $rollup = (int) $node['rollup_pct'];
    $tone = $rollup >= 80 ? 'emerald' : ($rollup >= 50 ? 'amber' : 'rose');
    $barClass = ['emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'rose' => 'bg-rose-500'][$tone];
    $pctClass = ['emerald' => 'text-emerald-600', 'amber' => 'text-amber-600', 'rose' => 'text-rose-600'][$tone];

    $typeChip = [
        'kpi' => ['KPI', 'bg-indigo-50 text-indigo-600'],
        'goal' => [__('performance_evaluation::goals.types.goal'), 'bg-[#f4f4f5] text-ink-muted'],
        'objective' => [__('performance_evaluation::goals.types.objective'), 'bg-blue-50 text-blue-600'],
    ][$node['goal_type']] ?? ['•', 'bg-zinc-100 text-zinc-500'];

    $statusChips = [
        'draft' => 'bg-zinc-100 text-zinc-500',
        'active' => 'bg-blue-50 text-blue-600',
        'at_risk' => 'bg-amber-50 text-amber-600',
        'done' => 'bg-emerald-50 text-emerald-600',
        'cancelled' => 'bg-rose-50 text-rose-500',
    ];
    $statusChip = $statusChips[$node['status']] ?? 'bg-zinc-100 text-zinc-500';
    $statusDots = [
        'draft' => 'bg-zinc-300',
        'active' => 'bg-blue-500',
        'at_risk' => 'bg-amber-500',
        'done' => 'bg-emerald-500',
        'cancelled' => 'bg-rose-400',
    ];

    $hasChildren = ! empty($node['children']);
    $pad = $depth * 22;
@endphp

<div class="border-b border-hairline-subtle last:border-b-0">
    <div class="group flex items-center gap-3 px-4 py-3 transition-colors hover:bg-[#fafafa]">
        <div class="flex min-w-0 flex-1 items-center gap-2" style="padding-left: {{ $pad }}px">
            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $typeChip[1] }}">{{ $typeChip[0] }}</span>
            <div class="min-w-0">
                <p class="truncate text-[13.5px] font-semibold tracking-[-0.01em] text-ink">{{ $node['title'] }}</p>
                <p class="truncate text-[12px] text-ink-faint">
                    @if ($node['personnel_name']){{ $node['personnel_name'] }}@else{{ __('performance_evaluation::goals.org_level') }}@endif
                    @if ($node['target'] > 0) · <span class="hrm-num">{{ rtrim(rtrim(number_format($node['current'], 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($node['target'], 2), '0'), '.') }}</span> {{ $node['unit'] }}@endif
                    @if ($node['due_date']) · {{ $node['due_date'] }}@endif
                    @if ($node['weight'] > 0) · <span class="hrm-num">{{ rtrim(rtrim(number_format($node['weight'], 2), '0'), '.') }}%</span>@endif
                </p>
            </div>
        </div>

        <div class="hidden w-40 shrink-0 items-center gap-2 sm:flex">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#f4f4f5]">
                <div class="h-full rounded-full {{ $barClass }} transition-all" style="width: {{ max(2, $rollup) }}%"></div>
            </div>
            <span class="hrm-num w-10 text-right text-[12.5px] font-semibold {{ $pctClass }}">{{ $rollup }}%</span>
        </div>

        @can('manage-performance-evaluation')
            {{-- status: a coloured pill backed by a native <select> — its option list is
                 rendered by the browser, so it is never clipped by ancestor overflow. --}}
            <div class="relative hidden shrink-0 sm:block">
                <select wire:change="setStatus({{ $node['id'] }}, $event.target.value)"
                    title="{{ __('performance_evaluation::goals.statuses.'.$node['status']) }}"
                    class="h-10 cursor-pointer appearance-none rounded-lg border-0 py-0 pl-3 pr-8 align-middle text-base font-semibold leading-10 sm:text-sm {{ $statusChip }} focus:outline-none focus:ring-2 focus:ring-zinc-200">
                    @foreach (['active','at_risk','done','cancelled'] as $st)
                        <option value="{{ $st }}" @selected($node['status'] === $st)>{{ __('performance_evaluation::goals.statuses.'.$st) }}</option>
                    @endforeach
                </select>
                <svg class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <button type="button" wire:click="startCheckin({{ $node['id'] }})" title="{{ __('performance_evaluation::goals.actions.checkin') }}"
                    class="flex h-10 w-10 items-center justify-center rounded-lg text-ink-faint transition-colors hover:bg-emerald-50 hover:text-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                </button>
                <button type="button"
                    x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('performance_evaluation::goals.confirm_delete')), run: () => $wire.deleteGoal({{ $node['id'] }}) })"
                    title="{{ __('performance_evaluation::goals.actions.delete') }}"
                    class="flex h-10 w-10 items-center justify-center rounded-lg transition-colors hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300">
                    <x-icons.delete-icon size="h-4 w-4" />
                </button>
            </div>
        @else
            <span class="hidden shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11.5px] font-medium sm:inline-flex {{ $statusChip }}"><span class="h-1.5 w-1.5 rounded-full {{ $statusDots[$node['status']] ?? 'bg-zinc-300' }}"></span>{{ __('performance_evaluation::goals.statuses.'.$node['status']) }}</span>
        @endcan
    </div>

    {{-- inline check-in --}}
    @if ($checkinGoalId === $node['id'])
        <div class="flex flex-wrap items-end gap-2.5 border-t border-hairline-subtle bg-[#fafafa] px-4 py-3.5" style="padding-left: {{ $pad + 24 }}px">
            <div class="w-36">
                <x-label value="{{ __('performance_evaluation::goals.fields.checkin_value') }}" />
                <x-livewire-input mode="default" type="number" step="0.01" name="checkinValue" wire:model="checkinValue" />
                @error('checkinValue') <x-validation>{{ $message }}</x-validation> @enderror
            </div>
            <div class="min-w-[12rem] flex-1">
                <x-label value="{{ __('performance_evaluation::goals.fields.checkin_note') }}" />
                <x-livewire-input mode="default" name="checkinNote" wire:model="checkinNote" />
            </div>
            <button type="button" wire:click="saveCheckin" class="h-[42px] rounded-xl bg-ink px-4 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __('performance_evaluation::goals.actions.save') }}</button>
            <button type="button" wire:click="cancelCheckin" class="h-[42px] rounded-xl border border-hairline bg-white px-4 text-[14px] font-medium text-ink-soft hover:border-zinc-300">{{ __('performance_evaluation::goals.actions.cancel') }}</button>
        </div>
    @endif
</div>

@foreach ($node['children'] as $child)
    @include('performance-evaluation::livewire.performance-evaluation.partials.goal-node', ['node' => $child, 'depth' => $depth + 1])
@endforeach
