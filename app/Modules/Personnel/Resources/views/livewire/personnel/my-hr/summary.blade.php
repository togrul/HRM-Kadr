@php
    $personnel = $this->personnel;
    $facts = [
        ['label' => __('personnel::my_hr.summary.tabel_no'), 'value' => $personnel->tabel_no ?: '—'],
        ['label' => __('personnel::my_hr.summary.position'), 'value' => $personnel->position?->name ?: '—'],
        // The unit name, with the full path only as a tooltip: a deep path turns the tile
        // into a paragraph and drags the whole row's height with it.
        ['label' => __('personnel::my_hr.summary.structure'), 'value' => $personnel->structure?->name ?: '—', 'title' => $personnel->structure?->fullStructureName(includeRoot: true) ?: ''],
        ['label' => __('personnel::my_hr.summary.email'), 'value' => $personnel->email ?: '—'],
        ['label' => __('personnel::my_hr.summary.mobile'), 'value' => $personnel->mobile ?: ($personnel->phone ?: '—')],
        ['label' => __('personnel::my_hr.summary.joined_at'), 'value' => optional($personnel->join_work_date)->format('d.m.Y') ?: '—'],
    ];

    $requestTone = fn (string $mode): string => match ($mode) {
        'warning' => 'amber',
        'success' => 'green',
        'info' => 'blue',
        'danger' => 'rose',
        default => 'secondary',
    };
@endphp

<div class="flex flex-col gap-4">
    {{-- ===================== employee profile ===================== --}}
    <section class="rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
            <x-avatar :name="$personnel->fullname" size="lg" />
            <div class="min-w-0">
                <p class="hrm-eyebrow">{{ __('personnel::my_hr.summary.profile_kicker') }}</p>
                <h2 class="mt-1 truncate text-[18px] font-semibold tracking-[-0.025em] text-ink">{{ $personnel->fullname }}</h2>
                <p class="mt-0.5 text-[12.5px] text-ink-faint">{{ __('personnel::my_hr.summary.employee_context') }}</p>
            </div>
        </div>

        <div class="grid gap-3 border-t border-hairline-subtle p-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($facts as $fact)
                <x-fact-tile :label="$fact['label']" :value="$fact['value']" :title="$fact['title'] ?? null" />
            @endforeach
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
        {{-- ===================== recent requests ===================== --}}
        <section class="rounded-xl border border-hairline bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <h3 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::my_hr.requests.title') }}</h3>
                <button type="button" wire:click="goto('requests')" wire:loading.attr="disabled" wire:target="goto"
                    class="shrink-0 text-[11.5px] text-ink-faint transition hover:text-ink">
                    {{ __('personnel::my_hr.requests.kicker') }}
                </button>
            </div>

            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentRequests as $row)
                    <div wire:key="my-hr-recent-{{ $row['id'] }}" class="flex items-center gap-3 px-4 py-3">
                        <span @class([
                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-[11px]',
                            'bg-[#fef3c7] text-[#b45309]' => $row['status_mode'] === 'warning',
                            'bg-[#d1fae5] text-[#047857]' => $row['status_mode'] === 'success',
                            'bg-[#e0f2fe] text-[#0369a1]' => $row['status_mode'] === 'info',
                            'bg-[#ffe4e6] text-[#be123c]' => $row['status_mode'] === 'danger',
                            'bg-[#f4f4f5] text-[#52525b]' => ! in_array($row['status_mode'], ['warning', 'success', 'info', 'danger'], true),
                        ])>
                            @if ($row['request_type'] === 'leave')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            @elseif ($row['request_type'] === 'business_trip')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2 16 11l3.5-3.5a2.1 2.1 0 0 0-3-3L13 8 4.8 6.2a.5.5 0 0 0-.5.8l3.2 4-2 2-2.3-.6a.5.5 0 0 0-.5.8L5 16l1.8 2.3a.5.5 0 0 0 .8-.5l-.6-2.3 2-2 4 3.2a.5.5 0 0 0 .8-.5Z"/></svg>
                            @else
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $row['title'] }}</p>
                            <p class="hrm-num mt-0.5 truncate text-[11.5px] text-ink-faint">{{ $row['period'] }}</p>
                        </div>

                        <x-small-badge :mode="$requestTone($row['status_mode'])" dot>{{ $row['status_label'] }}</x-small-badge>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <p class="text-[13px] font-medium text-ink">{{ __('personnel::my_hr.requests.empty.title') }}</p>
                        <p class="mt-1 text-[12px] text-ink-faint">{{ __('personnel::my_hr.requests.empty.body') }}</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- ===================== quick links ===================== --}}
        <section class="rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <p class="hrm-eyebrow">{{ __('personnel::my_hr.summary.quick_actions_kicker') }}</p>
                <h3 class="mt-1 text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::my_hr.summary.quick_actions_title') }}</h3>
            </div>

            <div class="grid gap-3 p-3 sm:grid-cols-2">
                @foreach ($this->quickActions as $action)
                    <button
                        type="button"
                        wire:key="my-hr-quick-{{ $action['key'] }}"
                        wire:click="goto('{{ $action['tab'] }}', '{{ $action['form'] }}')"
                        wire:loading.attr="disabled"
                        wire:target="goto"
                        class="flex h-[92px] flex-col items-start justify-between rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3 text-left transition hover:border-zinc-300 hover:bg-white hover:shadow-card"
                    >
                        <span class="text-ink-faint">
                            @if ($action['key'] === 'vacation')
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            @elseif ($action['key'] === 'leave')
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            @elseif ($action['key'] === 'business_trip')
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2 16 11l3.5-3.5a2.1 2.1 0 0 0-3-3L13 8 4.8 6.2a.5.5 0 0 0-.5.8l3.2 4-2 2-2.3-.6a.5.5 0 0 0-.5.8L5 16l1.8 2.3a.5.5 0 0 0 .8-.5l-.6-2.3 2-2 4 3.2a.5.5 0 0 0 .8-.5Z"/></svg>
                            @else
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3h16v18l-3-2-2 2-2-2-2 2-2-2-3 2Z"/><path d="M8 8h8M8 12h6"/></svg>
                            @endif
                        </span>
                        <span class="text-[12.5px] font-semibold leading-snug tracking-[-0.01em] text-ink">{{ $action['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>
    </div>
</div>
