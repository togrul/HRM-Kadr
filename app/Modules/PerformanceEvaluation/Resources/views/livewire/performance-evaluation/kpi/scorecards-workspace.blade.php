@php
    $t = 'performance_evaluation::kpi';
    // Spec §9 colour code: below threshold red, threshold–target amber, at/over target green.
    $cardBadge = [
        'draft' => ['bg-[#f4f4f5] text-ink-muted', 'bg-zinc-400'],
        'active' => ['bg-sky-50 text-sky-700', 'bg-sky-500'],
        'manager_review' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
        'closed' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
    ];
    $scoreTone = fn ($score, $threshold = 80) => $score === null ? 'text-zinc-400' : ((float) $score >= 100 ? 'text-emerald-600' : ((float) $score >= (float) ($threshold ?? 80) ? 'text-amber-600' : 'text-rose-600'));
    $fmt = fn ($value, int $decimals = 2) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, $decimals, '.', ' '), '0'), '.');
    $card = $this->card;
    $role = $this->role;
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-5">
    {{-- ───────────── toolbar ───────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="min-w-[13rem]">
                <x-ui.filter-native-select wire:model.live="cycleId">
                    @foreach ($this->cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </div>
            <div class="min-w-[11rem]">
                <x-ui.filter-native-select wire:model.live="statusFilter">
                    <option value="">{{ __($t.'.all_statuses') }}</option>
                    @foreach (\App\Models\PerformanceScorecard::STATUSES as $status)
                        <option value="{{ $status }}">{{ __($t.'.card_statuses.'.$status) }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </div>
        </div>

        @can('manage-performance-evaluation')
            @if ($cycleId)
                <x-pill-button variant="primary" wire:click="generate" wire:loading.attr="disabled" wire:target="generate">
                    <x-icons.add-icon size="h-4 w-4" color="text-current" hover="text-current" />
                    {{ __($t.'.actions.generate_cards') }}
                </x-pill-button>
            @endif
        @endcan
    </div>

    @if ($card)
        {{-- ───────────── one card ───────────── --}}
        <div class="rounded-2xl border border-hairline bg-white shadow-card">
            <div class="flex flex-col gap-4 border-b border-hairline-subtle p-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <button type="button" wire:click="closeCard" class="text-[12px] font-medium text-zinc-400 hover:text-zinc-700">← {{ __($t.'.actions.back_to_list') }}</button>
                    <h2 class="mt-1.5 truncate text-[18px] font-semibold tracking-tight text-zinc-950">{{ $card->personnel?->fullname }}</h2>
                    <p class="mt-0.5 text-[12px] text-zinc-500">
                        {{ $card->position?->name ?? '—' }} ·
                        {{ __($t.'.fields.manager') }}: {{ $card->manager ? trim($card->manager->surname.' '.$card->manager->name) : '—' }} ·
                        {{ $card->valid_from?->format('d.m.Y') }} – {{ $card->valid_to?->format('d.m.Y') }}
                        @if ((float) $card->prorata_factor < 1)
                            · {{ __($t.'.fields.prorata') }} {{ $fmt((float) $card->prorata_factor * 100) }}%
                        @endif
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-5">
                    <div class="text-right">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __($t.'.fields.kpi_score') }}</p>
                        <p class="text-[1.5rem] font-semibold hrm-num {{ $scoreTone($card->kpi_score) }}">{{ $fmt($card->kpi_score) }}%</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __($t.'.fields.final_score') }}</p>
                        <p class="text-[1.5rem] font-semibold hrm-num {{ $scoreTone($card->final_score) }}">{{ $fmt($card->final_score) }}{{ $card->final_score !== null ? '%' : '' }}</p>
                        @if ($card->rating_category)
                            <p class="text-[11px] text-zinc-500">{{ __($t.'.ratings.'.$card->rating_category) }}</p>
                        @endif
                    </div>
                    @php [$badgeTone, $badgeDot] = $cardBadge[$card->status] ?? $cardBadge['draft']; @endphp
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11.5px] font-medium {{ $badgeTone }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $badgeDot }}"></span>
                        {{ __($t.'.card_statuses.'.$card->status) }}
                    </span>
                </div>
            </div>

            {{-- workflow actions --}}
            @php
                $actions = collect(\App\Models\PerformanceScorecard::TRANSITIONS)
                    ->filter(fn ($rule, $action) => $rule['from'] === $card->status)
                    ->filter(fn ($rule, $action) => in_array($action, ['return', 'close'], true) ? $role === 'hr' : in_array($role, ['hr', 'manager'], true));
            @endphp
            @if ($actions->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 border-b border-hairline-subtle px-5 py-3">
                    @foreach ($actions as $action => $rule)
                        <button type="button" wire:key="card-action-{{ $action }}"
                            x-on:click="$dispatch('confirm-action', { tone: @js($action === 'return' ? 'amber' : 'emerald'), message: @js(__($t.'.confirm_transition.'.$action)), run: () => $wire.moveCard(@js($action)) })"
                            class="h-9 rounded-xl px-4 text-[13px] font-semibold {{ $action === 'return' ? 'border border-zinc-200 text-zinc-700 hover:bg-zinc-50' : 'bg-zinc-900 text-white hover:bg-zinc-800' }}">
                            {{ __($t.'.transitions.'.$action) }}
                        </button>
                    @endforeach
                    @error('scorecard') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-[13px]">
                    <thead class="hrm-eyebrow border-b border-hairline-subtle bg-[#fafafa]">
                        <tr>
                            <th class="px-4 py-3">{{ __($t.'.fields.kpi') }}</th>
                            <th class="px-4 py-3 text-right">{{ __($t.'.fields.weight') }}</th>
                            <th class="px-4 py-3 text-right">{{ __($t.'.fields.target') }}</th>
                            <th class="px-4 py-3 text-right">{{ __($t.'.fields.band') }}</th>
                            <th class="px-4 py-3 text-right">{{ __($t.'.fields.actual') }}</th>
                            <th class="px-4 py-3 text-right">{{ __($t.'.fields.achievement') }}</th>
                            <th class="px-4 py-3 text-right">{{ __($t.'.fields.score') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline-subtle">
                        @foreach ($card->items as $item)
                            <tr wire:key="card-item-{{ $item->id }}" class="align-top text-zinc-700">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-zinc-900">{{ $item->kpi?->name }}</p>
                                    <p class="text-[11px] text-zinc-400">{{ $item->kpi?->code }} · {{ __($t.'.directions.'.$item->kpi?->direction) }} · {{ __($t.'.units.'.$item->kpi?->unit) }}</p>
                                </td>
                                <td class="px-4 py-3 text-right hrm-num">{{ $fmt($item->weight) }}%</td>
                                <td class="px-4 py-3 text-right hrm-num">
                                    @if ($card->status === 'draft' && ($role === 'hr' || ($role === 'manager' && $item->target_editable)))
                                        <div class="flex items-center justify-end gap-1">
                                            <input type="number" step="any" wire:model="targets.{{ $item->id }}" class="h-8 w-24 rounded-lg border border-zinc-200 bg-zinc-50 px-2 text-right text-[12px] focus:outline-none">
                                            <button type="button" wire:click="saveTarget({{ $item->id }})" class="rounded-lg px-1.5 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50">{{ __($t.'.actions.save') }}</button>
                                        </div>
                                        @error('targets.'.$item->id) <x-validation>{{ $message }}</x-validation> @enderror
                                    @elseif ($item->kpi?->direction === 'range')
                                        {{ $fmt($item->range_min) }} – {{ $fmt($item->range_max) }}
                                    @else
                                        {{ $fmt($item->target) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-[12px] hrm-num text-zinc-500">{{ $fmt($item->threshold) }} / {{ $fmt($item->stretch) }} / {{ $fmt($item->cap) }}</td>
                                <td class="px-4 py-3 text-right hrm-num">{{ $fmt($item->actual) }}</td>
                                <td class="px-4 py-3 text-right hrm-num">{{ $item->achievement === null ? '—' : $fmt($item->achievement).'%' }}</td>
                                <td class="px-4 py-3 text-right font-semibold hrm-num {{ $scoreTone($item->score, $item->threshold) }}">{{ $item->score === null ? '—' : $fmt($item->score).'%' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($card->status === 'active' && $role !== null && $actualItemId !== $item->id)
                                        <button type="button" wire:click="startActual({{ $item->id }})" class="rounded-lg px-2 py-1 text-[12px] font-medium text-zinc-600 hover:bg-zinc-100">{{ __($t.'.actions.add_actual') }}</button>
                                    @endif
                                </td>
                            </tr>

                            @if ($actualItemId === $item->id)
                                <tr wire:key="card-item-form-{{ $item->id }}">
                                    <td colspan="8" class="bg-zinc-50/70 px-4 py-3">
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                            <label class="block">
                                                <span class="text-[11px] text-zinc-500">{{ __($t.'.fields.actual') }}</span>
                                                <input type="number" step="any" wire:model="actualValue" class="h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-[13px] focus:outline-none">
                                                @error('actualValue') <x-validation>{{ $message }}</x-validation> @enderror
                                                @error('actual') <x-validation>{{ $message }}</x-validation> @enderror
                                            </label>
                                            <label class="block sm:col-span-2">
                                                <span class="text-[11px] text-zinc-500">{{ __($t.'.fields.note') }}</span>
                                                <input type="text" wire:model="actualNote" class="h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-[13px] focus:outline-none">
                                            </label>
                                            <label class="block">
                                                <span class="text-[11px] text-zinc-500">
                                                    {{ __($t.'.fields.evidence') }}
                                                    @if ($item->kpi?->evidence_required) <span class="text-rose-600">*</span> @endif
                                                </span>
                                                <input type="file" wire:model="evidence" class="block w-full text-[12px] text-zinc-600 file:mr-2 file:rounded-lg file:border-0 file:bg-zinc-200 file:px-2 file:py-1.5">
                                                @error('evidence') <x-validation>{{ $message }}</x-validation> @enderror
                                            </label>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between gap-2">
                                            <p class="text-[11px] text-zinc-400">{{ $role === 'employee' ? __($t.'.actual_pending_hint') : '' }}</p>
                                            <div class="flex gap-2">
                                                <button type="button" wire:click="cancelActual" class="h-9 rounded-xl border border-zinc-200 px-4 text-[13px] text-zinc-600 hover:bg-white">{{ __($t.'.actions.cancel') }}</button>
                                                <button type="button" wire:click="saveActual" wire:loading.attr="disabled" wire:target="saveActual,evidence" class="h-9 rounded-xl bg-emerald-600 px-4 text-[13px] font-semibold text-white hover:bg-emerald-500">{{ __($t.'.actions.save') }}</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            @if ($item->actuals->isNotEmpty())
                                <tr wire:key="card-item-history-{{ $item->id }}">
                                    <td colspan="8" class="px-4 pb-3 pt-0">
                                        <div class="flex flex-col gap-1">
                                            @foreach ($item->actuals->take(5) as $actual)
                                                <div wire:key="actual-{{ $actual->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-zinc-500">
                                                    <span class="hrm-num font-medium text-zinc-700">{{ $fmt($actual->value, 4) }}</span>
                                                    <span>{{ $actual->enteredBy?->name ?? '—' }} · {{ $actual->created_at?->format('d.m.Y H:i') }}</span>
                                                    @if ($actual->evidence_name)
                                                        <span class="text-zinc-400">{{ __($t.'.fields.evidence') }}: {{ $actual->evidence_name }}</span>
                                                    @endif
                                                    @if ($actual->note)
                                                        <span class="text-zinc-400">“{{ $actual->note }}”</span>
                                                    @endif
                                                    @if ($actual->approved_at)
                                                        <span class="rounded-full bg-emerald-50 px-1.5 text-emerald-700">{{ __($t.'.actual_approved') }}</span>
                                                    @else
                                                        <span class="rounded-full bg-amber-50 px-1.5 text-amber-700">{{ __($t.'.actual_pending') }}</span>
                                                        @if (in_array($role, ['hr', 'manager'], true) && $card->status === 'active')
                                                            <button type="button" wire:click="approveActual({{ $actual->id }})" class="font-semibold text-emerald-700 hover:underline">{{ __($t.'.actions.approve') }}</button>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
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
    @endif
</div>
