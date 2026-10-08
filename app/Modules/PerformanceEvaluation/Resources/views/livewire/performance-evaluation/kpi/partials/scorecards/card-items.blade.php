{{-- ───────────── KPI items ───────────── --}}
<div class="{{ $section }}">
    <div class="{{ $sectionHead }}">
        <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.card_kpis') }}</p>
        <span class="hrm-num text-[11.5px] text-ink-faint">{{ (float) $card->kpi_weight_share }}%</span>
    </div>
    <div class="hrm-scroll overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-[13px]">
            <thead class="hrm-eyebrow whitespace-nowrap border-b border-hairline-subtle">
                <tr>
                    <th class="px-5 py-2.5">{{ __($t.'.fields.kpi') }}</th>
                    <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.weight') }}</th>
                    <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.target') }}</th>
                    <th class="px-3 py-2.5 text-right" title="{{ __($t.'.band_title') }}">{{ __($t.'.fields.band') }}</th>
                    <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.actual') }}</th>
                    <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.achievement') }}</th>
                    <th class="px-3 py-2.5 text-right">{{ __($t.'.fields.score') }}</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-hairline-subtle">
                @foreach ($card->items as $item)
                    <tr wire:key="card-item-{{ $item->id }}" class="align-top text-ink-soft">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-ink">
                                {{ $item->kpi?->name }}
                                @if ($item->kpi?->source_metric)
                                    <span class="ml-1 rounded-md bg-sky-50 px-1.5 py-px text-[10.5px] font-medium text-sky-700">{{ __($t.'.metrics.auto_badge') }}</span>
                                @endif
                                @if ($item->kpi?->integration_error)
                                    <span class="ml-1 rounded-md bg-rose-50 px-1.5 py-px text-[10.5px] font-medium text-rose-700" title="{{ $item->kpi->integration_error }}">{{ __($t.'.connector.stale') }}</span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-[11.5px] text-ink-faint"><span class="hrm-num">{{ $item->kpi?->code }}</span> · {{ __($t.'.directions_short.'.$item->kpi?->direction) }} · {{ __($t.'.units.'.$item->kpi?->unit) }}</p>
                            @if ($card->status === 'draft' && in_array($role, ['hr', 'manager'], true) && $this->goalOptions !== [])
                                <x-ui.select wire:change="linkGoal({{ $item->id }}, $event.target.value)" aria-label="{{ __($t.'.a11y.goal', ['kpi' => $item->kpi?->name]) }}" class="mt-1.5 max-w-[260px]">
                                    <option value="">{{ __($t.'.no_goal') }}</option>
                                    @foreach ($this->goalOptions as $goalId => $goalTitle)
                                        <option value="{{ $goalId }}" @selected((int) $item->performance_goal_id === (int) $goalId)>{{ $goalTitle }}</option>
                                    @endforeach
                                </x-ui.select>
                            @elseif ($item->goal)
                                <p class="mt-1 inline-flex items-center gap-1 rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-[11px] text-ink-muted">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>
                                    {{ $item->goal->title }}
                                </p>
                            @endif
                        </td>
                        <td class="hrm-num px-3 py-3 text-right">{{ $fmt($item->weight) }}%</td>
                        <td class="px-3 py-3 text-right">
                            @if ($card->status === 'draft' && ($role === 'hr' || ($role === 'manager' && $item->target_editable)))
                                <div class="ml-auto inline-flex h-10 items-center overflow-hidden rounded-lg border border-hairline bg-white transition focus-within:border-zinc-400 focus-within:ring-2 focus-within:ring-zinc-900/5">
                                    <input type="number" step="any" wire:model="targets.{{ $item->id }}" wire:keydown.enter="saveTarget({{ $item->id }})" aria-label="{{ __($t.'.fields.target') }}"
                                        class="hrm-num h-full w-24 border-0 bg-transparent px-2 text-right text-base text-ink focus:outline-none focus:ring-0 sm:text-sm [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                    <button type="button" wire:click="saveTarget({{ $item->id }})" title="{{ __($t.'.actions.save') }}" aria-label="{{ __($t.'.actions.save') }}"
                                        class="flex h-full w-10 items-center justify-center border-l border-hairline text-ink-faint transition hover:bg-emerald-50 hover:text-emerald-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                    </button>
                                </div>
                                @error('targets.'.$item->id) <x-validation>{{ $message }}</x-validation> @enderror
                            @elseif ($item->kpi?->direction === 'range')
                                <span class="hrm-num">{{ $fmt($item->range_min) }} – {{ $fmt($item->range_max) }}</span>
                            @else
                                <span class="hrm-num">{{ $fmt($item->target) }}</span>
                                @if ($item->original_target !== null)
                                    <span class="block text-[11px] text-ink-faint line-through decoration-ink-faint/60" title="{{ __($t.'.leave.original', ['value' => $fmt($item->original_target)]) }}">{{ $fmt($item->original_target) }}</span>
                                @endif
                                @php $pendingChange = $item->changeRequests->firstWhere('status', 'pending'); @endphp
                                @if ($pendingChange)
                                    <span class="mt-1 block whitespace-nowrap rounded-md bg-violet-50 px-1.5 py-0.5 text-[11px] font-medium text-violet-700" title="{{ $pendingChange->reason }}">{{ __($t.'.change_requests.pending_badge', ['to' => $fmt($pendingChange->proposed_target)]) }}</span>
                                @elseif ($card->status === 'active' && $role !== null && $item->kpi?->type === 'quantitative' && $changeItemId !== $item->id)
                                    <button type="button" wire:click="startChange({{ $item->id }})" class="mt-1 block w-full text-right text-[11px] font-medium text-ink-faint underline-offset-2 hover:text-ink hover:underline">{{ __($t.'.change_requests.ask') }}</button>
                                @endif
                            @endif
                        </td>
                        <td class="hrm-num whitespace-nowrap px-3 py-3 text-right text-[12px] text-ink-faint" title="{{ __($t.'.band_title') }}">{{ $fmt($item->threshold) }} · {{ $fmt($item->stretch) }} · {{ $fmt($item->cap) }}</td>
                        <td class="hrm-num px-3 py-3 text-right">{{ $fmt($item->actual) }}</td>
                        <td class="hrm-num px-3 py-3 text-right">
                            {{ $item->achievement === null ? '—' : $fmt($item->achievement).'%' }}
                            @if ($item->forecast_achievement !== null && $card->status === 'active')
                                @php $red = $item->threshold !== null && (float) $item->forecast_achievement < (float) $item->threshold; @endphp
                                <span class="mt-0.5 block whitespace-nowrap text-[11px] {{ $red ? 'font-semibold text-rose-600' : 'text-ink-faint' }}" title="{{ __($t.'.forecast.hint', ['value' => $fmt($item->forecast)]) }}">{{ __($t.'.forecast.label', ['value' => $fmt($item->forecast_achievement, 1)]) }}</span>
                            @endif
                        </td>
                        <td class="hrm-num px-3 py-3 text-right font-semibold {{ $scoreTone($item->score, $item->threshold) }}">{{ $item->score === null ? '—' : $fmt($item->score).'%' }}</td>
                        <td class="px-5 py-3 text-right">
                            @if ($card->status === 'active' && $role !== null && $actualItemId !== $item->id && $item->kpi?->data_source !== 'calculated')
                                <button type="button" wire:click="startActual({{ $item->id }})" class="whitespace-nowrap rounded-lg border border-hairline px-2.5 h-10 text-[14px] font-medium text-ink-soft hover:bg-[#f4f4f5]">{{ __($t.'.actions.add_actual') }}</button>
                            @endif
                        </td>
                    </tr>

                    @if ($changeItemId === $item->id)
                        <tr wire:key="card-item-change-{{ $item->id }}">
                            <td colspan="8" class="bg-violet-50/40 px-5 py-3">
                                <p class="text-[12px] font-semibold text-ink">{{ __($t.'.change_requests.form_title', ['kpi' => $item->kpi?->name]) }}</p>
                                <p class="mt-0.5 text-[11.5px] text-ink-muted">{{ __($t.'.change_requests.form_hint') }}</p>
                                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-[160px_minmax(0,1fr)_auto] sm:items-start">
                                    <label class="block">
                                        <span class="text-[11px] text-ink-muted">{{ __($t.'.change_requests.proposed') }}</span>
                                        <input type="number" step="any" wire:model="changeTarget" class="{{ $field }}">
                                        @error('changeTarget') <x-validation>{{ $message }}</x-validation> @enderror
                                    </label>
                                    <label class="block">
                                        <span class="text-[11px] text-ink-muted">{{ __($t.'.change_requests.reason') }}</span>
                                        <input type="text" wire:model="changeReason" class="{{ $field }}" placeholder="{{ __($t.'.reason_placeholder') }}">
                                        @error('reason') <x-validation>{{ $message }}</x-validation> @enderror
                                        @error('change') <x-validation>{{ $message }}</x-validation> @enderror
                                    </label>
                                    <div class="flex items-center gap-2 sm:pt-5">
                                        <button type="button" wire:click="cancelChange" class="h-10 rounded-xl px-3 text-[14px] font-medium text-ink-muted hover:bg-white">{{ __($t.'.actions.cancel') }}</button>
                                        <button type="button" wire:click="submitChange" class="h-10 rounded-xl bg-ink px-4 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.change_requests.send') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif

                    @if ($actualItemId === $item->id)
                        <tr wire:key="card-item-form-{{ $item->id }}">
                            <td colspan="8" class="bg-[#fafafa] px-5 py-3">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                    <label class="block">
                                        <span class="text-[11px] text-ink-muted">{{ __($t.'.fields.actual') }}</span>
                                        <input type="number" step="any" wire:model="actualValue" class="{{ $field }}">
                                        @error('actualValue') <x-validation>{{ $message }}</x-validation> @enderror
                                        @error('actual') <x-validation>{{ $message }}</x-validation> @enderror
                                    </label>
                                    <label class="block sm:col-span-2">
                                        <span class="text-[11px] text-ink-muted">{{ __($t.'.fields.note') }}</span>
                                        <input type="text" wire:model="actualNote" class="{{ $field }}">
                                    </label>
                                    <label class="block">
                                        <span class="text-[11px] text-ink-muted">
                                            {{ __($t.'.fields.evidence') }}
                                            @if ($item->kpi?->evidence_required) <span class="text-rose-600">*</span> @endif
                                        </span>
                                        <input type="file" wire:model="evidence" class="block w-full text-[12px] text-ink-muted file:mr-2 file:rounded-lg file:border-0 file:bg-[#f4f4f5] file:px-2 file:py-1.5">
                                        @error('evidence') <x-validation>{{ $message }}</x-validation> @enderror
                                    </label>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <p class="text-[11px] text-ink-faint">{{ $role === 'employee' ? __($t.'.actual_pending_hint') : '' }}</p>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="cancelActual" class="h-10 rounded-xl border border-hairline px-4 text-[14px] text-ink-soft hover:bg-white">{{ __($t.'.actions.cancel') }}</button>
                                        <button type="button" wire:click="saveActual" wire:loading.attr="disabled" wire:target="saveActual,evidence" class="h-10 rounded-xl bg-ink px-4 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.save') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif

                    @if ($item->actuals->isNotEmpty())
                        <tr wire:key="card-item-history-{{ $item->id }}">
                            <td colspan="8" class="px-5 pb-3 pt-0">
                                <div class="flex flex-col gap-1 border-l-2 border-hairline pl-3">
                                    @foreach ($item->actuals->take(5) as $actual)
                                        <div wire:key="actual-{{ $actual->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px] text-ink-muted">
                                            <span class="hrm-num font-medium text-ink">{{ $fmt($actual->value, 4) }}</span>
                                            <span>{{ $actual->enteredBy?->name ?? '—' }} · <span class="hrm-num">{{ $actual->created_at?->format('d.m.Y H:i') }}</span></span>
                                            @if ($actual->evidence_name)
                                                <span class="text-ink-faint">{{ __($t.'.fields.evidence') }}: {{ $actual->evidence_name }}</span>
                                            @endif
                                            @if ($actual->note)
                                                <span class="text-ink-faint">“{{ $actual->note }}”</span>
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
