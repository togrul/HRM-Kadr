{{-- ───────────── calibration spread + cascade gaps (HR) ───────────── --}}
@php $spread = $this->distribution; $spreadTotal = collect($spread)->sum('count'); @endphp
@if ($spreadTotal > 0 || $this->unlinkedItems > 0)
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        @if ($spreadTotal > 0)
            <div class="{{ $section }} px-5 py-4">
                <div class="flex items-center justify-between">
                    <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.distribution') }}</p>
                    <span class="hrm-num text-[11.5px] text-ink-faint">{{ $spreadTotal }}</span>
                </div>
                <div class="mt-3 grid grid-cols-5 gap-3">
                    @foreach ($spread as $category => $row)
                        <div>
                            <div class="relative h-20 overflow-hidden rounded-lg bg-[#f4f4f5]">
                                <div class="absolute inset-x-0 bottom-0 bg-ink" style="height: {{ min(100, $row['share']) }}%"></div>
                                <div class="absolute inset-x-0 border-t-2 border-dashed border-orange-500" style="bottom: {{ $row['target'] }}%"></div>
                            </div>
                            <p class="hrm-num mt-1.5 text-[12px] font-semibold text-ink">{{ $row['share'] }}% <span class="font-normal text-ink-faint">/ {{ $row['target'] }}%</span></p>
                            <p class="text-[11px] leading-tight text-ink-faint">{{ __($t.'.ratings.'.$category) }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-[11px] text-ink-faint">{{ __($t.'.distribution_hint') }}</p>
            </div>
        @endif
        @if ($this->unlinkedItems > 0)
            <div class="{{ $section }} px-5 py-4">
                <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.cascade_gaps') }}</p>
                <p class="hrm-num mt-2 text-[28px] font-semibold leading-none text-amber-600">{{ $this->unlinkedItems }}</p>
                <p class="mt-1.5 text-[12px] text-ink-muted">{{ __($t.'.cascade_gaps_hint') }}</p>
            </div>
        @endif
    </div>
@endif

{{-- ───────────── card list ───────────── --}}
@php $cardColumns = 'md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.3fr)_150px_88px_88px] md:items-center md:gap-4'; @endphp
<div class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
    <div class="{{ $cardColumns }} hidden border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5">
        <span class="hrm-eyebrow">{{ __($t.'.fields.personnel') }}</span>
        <span class="hrm-eyebrow">{{ __($t.'.fields.position') }}</span>
        <span class="hrm-eyebrow">{{ __($t.'.fields.manager') }}</span>
        <span class="hrm-eyebrow">{{ __($t.'.fields.status') }}</span>
        <span class="hrm-eyebrow text-right">{{ __($t.'.fields.kpi_score') }}</span>
        <span class="hrm-eyebrow text-right">{{ __($t.'.fields.final_score') }}</span>
    </div>

    @forelse ($this->cards as $row)
        @php [$badgeTone, $badgeDot] = $cardBadge[$row->status] ?? $cardBadge['draft']; @endphp
        <button type="button" wire:key="card-row-{{ $row->id }}" wire:click="openCard({{ $row->id }})"
            class="{{ $cardColumns }} flex w-full flex-col gap-2 border-b border-hairline-subtle px-5 py-3 text-left transition-colors last:border-b-0 hover:bg-[#fafafa] focus-visible:bg-[#fafafa] focus-visible:outline-none">
            <span class="flex min-w-0 items-center gap-3">
                <x-avatar size="sm" :name="$row->personnel?->fullname ?? '—'" />
                <span class="truncate text-[13.5px] font-semibold tracking-[-0.01em] text-ink">{{ $row->personnel?->fullname }}</span>
            </span>
            <span class="truncate text-[12.5px] text-ink-soft">{{ $row->position?->name ?? '—' }}</span>
            <span class="truncate text-[12.5px] text-ink-muted">{{ $row->manager ? trim($row->manager->surname.' '.$row->manager->name) : '—' }}</span>
            <span>
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $badgeTone }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $badgeDot }}"></span>
                    {{ __($t.'.card_statuses.'.$row->status) }}
                </span>
            </span>
            <span class="hrm-num text-[13px] md:text-right {{ $scoreTone($row->kpi_score) }}">{{ $row->kpi_score === null ? '—' : $fmt($row->kpi_score).'%' }}</span>
            <span class="hrm-num text-[13px] font-semibold md:text-right {{ $scoreTone($row->final_score) }}">{{ $row->final_score === null ? '—' : $fmt($row->final_score).'%' }}</span>
        </button>
    @empty
        <div class="px-6 py-16 text-center">
            <p class="text-[13.5px] font-medium text-ink">{{ $cycleId ? __($t.'.empty_cards') : __($t.'.no_cycle') }}</p>
        </div>
    @endforelse
</div>
