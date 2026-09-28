{{-- ───────────── competencies ───────────── --}}
@if ($competencies->isNotEmpty())
    @php
        $canSelf = $card->status === 'self_review' && $role === 'employee';
        $canManager = $card->status === 'manager_review' && in_array($role, ['manager', 'hr'], true);
        $showSelf = $role !== 'employee' || in_array($card->status, ['self_review', 'manager_review', 'calibration', 'approved', 'closed'], true);
    @endphp
    <div class="{{ $section }}">
        <div class="{{ $sectionHead }}">
            <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.competencies') }}</p>
            <span class="hrm-num text-[11.5px] text-ink-faint">{{ (float) $card->competency_weight_share }}%</span>
        </div>
        <p class="px-5 pt-3 text-[12px] text-ink-faint">{{ __($t.'.competency_hint') }}</p>
        @error('competency') <div class="px-5"><x-validation>{{ $message }}</x-validation></div> @enderror
        <div class="divide-y divide-hairline-subtle">
            @foreach ($competencies as $competency)
                <div wire:key="competency-{{ $competency['id'] }}" class="grid grid-cols-1 gap-3 px-5 py-3 md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] md:items-center">
                    <div class="min-w-0">
                        <p class="truncate text-[13px] font-semibold text-ink">{{ $competency['name'] }}</p>
                        <p class="text-[11.5px] text-ink-faint">{{ $competency['section'] }}</p>
                    </div>
                    @foreach (['self' => [$canSelf, $showSelf], 'manager' => [$canManager, true]] as $evaluator => [$editable, $visible])
                        <div>
                            <p class="hrm-eyebrow mb-1">{{ __($t.'.evaluators.'.$evaluator) }}</p>
                            @if ($visible)
                                <div class="flex gap-1">
                                    @foreach (range(1, 5) as $rating)
                                        @php $on = $competency[$evaluator] === $rating; @endphp
                                        @if ($editable)
                                            <button type="button" wire:click="rate({{ $competency['id'] }}, '{{ $evaluator }}', {{ $rating }})"
                                                class="hrm-num h-10 w-10 rounded-[10px] border text-[14px] font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 {{ $on ? 'border-ink bg-ink text-white' : 'border-hairline bg-white text-ink-muted hover:border-zinc-400 hover:text-ink' }}">{{ $rating }}</button>
                                        @else
                                            <span class="hrm-num flex h-10 w-10 items-center justify-center rounded-[10px] text-[14px] font-semibold {{ $on ? 'bg-ink text-white' : 'bg-[#f4f4f5] text-ink-faint' }}">{{ $rating }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <p class="text-[12px] text-ink-faint">{{ __($t.'.hidden_until_self_review') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    {{-- ───────────── check-ins ───────────── --}}
    <div class="{{ $section }}">
        <div class="{{ $sectionHead }}">
            <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.checkins') }}</p>
            <span class="hrm-num text-[11.5px] {{ $card->checkins->count() >= $expectedCheckins ? 'text-emerald-700' : 'text-ink-faint' }}">{{ $card->checkins->count() }} / {{ $expectedCheckins }}</span>
        </div>
        @if ($card->status === 'active' && $role !== null)
            <div class="grid grid-cols-1 gap-2 border-b border-hairline-subtle px-5 py-3">
                <div class="grid grid-cols-[140px_minmax(0,1fr)] gap-2">
                    <label class="block">
                        <span class="text-[11px] text-ink-muted">{{ __($t.'.a11y.checkin_date') }}</span>
                        <input type="date" wire:model="checkinDate" class="{{ $field }}">
                    </label>
                    <label class="block">
                        <span class="text-[11px] text-ink-muted">{{ __($t.'.a11y.checkin_progress') }}</span>
                        <input type="text" wire:model="checkinProgress" placeholder="{{ __($t.'.checkin_progress_placeholder') }}" class="{{ $field }}">
                    </label>
                </div>
                <label class="block">
                    <span class="text-[11px] text-ink-muted">{{ __($t.'.a11y.checkin_risks') }}</span>
                    <input type="text" wire:model="checkinRisks" placeholder="{{ __($t.'.checkin_risks_placeholder') }}" class="{{ $field }}">
                </label>
                @error('checkinProgress') <x-validation>{{ $message }}</x-validation> @enderror
                @error('checkin') <x-validation>{{ $message }}</x-validation> @enderror
                <div class="flex justify-end">
                    <button type="button" wire:click="saveCheckin" class="h-10 rounded-xl bg-ink px-4 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.add_checkin') }}</button>
                </div>
            </div>
        @endif
        <div class="hrm-scroll max-h-72 divide-y divide-hairline-subtle overflow-y-auto">
            @forelse ($card->checkins as $checkin)
                <div wire:key="checkin-{{ $checkin->id }}" class="px-5 py-3">
                    <p class="text-[11.5px] text-ink-faint"><span class="hrm-num">{{ $checkin->checkin_date?->format('d.m.Y') }}</span> · {{ $checkin->author?->name ?? '—' }}</p>
                    <p class="mt-1 text-[13px] text-ink-soft">{{ $checkin->progress }}</p>
                    @if ($checkin->risks)
                        <p class="mt-1 text-[12px] text-amber-700">{{ __($t.'.risks') }}: {{ $checkin->risks }}</p>
                    @endif
                </div>
            @empty
                <p class="px-5 py-6 text-center text-[12.5px] text-ink-faint">{{ __($t.'.no_checkins') }}</p>
            @endforelse
        </div>
    </div>

    {{-- ───────────── history (+ calibration) ───────────── --}}
    <div class="flex flex-col gap-4">
        @if ($card->status === 'calibration' && $role === 'hr')
            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.calibration') }}</p>
                    <span class="text-[11.5px] text-ink-faint">± {{ \App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardReviewService::MAX_CALIBRATION_DELTA }}</span>
                </div>
                <div class="grid grid-cols-1 gap-2 px-5 py-3">
                    <div class="grid grid-cols-[120px_minmax(0,1fr)] gap-2">
                        <label class="block">
                            <span class="text-[11px] text-ink-muted">{{ __($t.'.a11y.calibration_delta') }}</span>
                            <input type="number" step="0.5" wire:model="calibrationDelta" placeholder="+/-" class="{{ $field }} hrm-num">
                        </label>
                        <label class="block">
                            <span class="text-[11px] text-ink-muted">{{ __($t.'.a11y.transition_reason') }}</span>
                            <input type="text" wire:model="calibrationReason" placeholder="{{ __($t.'.reason_placeholder') }}" class="{{ $field }}">
                        </label>
                    </div>
                    @error('calibrationDelta') <x-validation>{{ $message }}</x-validation> @enderror
                    @error('calibrationReason') <x-validation>{{ $message }}</x-validation> @enderror
                    @error('calibration') <x-validation>{{ $message }}</x-validation> @enderror
                    <div class="flex justify-end">
                        <button type="button" wire:click="saveCalibration" class="h-10 rounded-xl bg-ink px-4 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.calibrate') }}</button>
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($t.'.sections.history') }}</p>
            </div>
            <ol class="hrm-scroll max-h-72 overflow-y-auto px-5 py-3">
                @foreach ($card->calibrations as $adjustment)
                    <li wire:key="calibration-{{ $adjustment->id }}" class="relative border-l border-hairline pb-3 pl-4">
                        <span class="absolute -left-[4.5px] top-1.5 h-2 w-2 rounded-full bg-orange-500"></span>
                        <p class="text-[12.5px] font-medium text-ink">{{ __($t.'.calibration_entry', ['delta' => ((float) $adjustment->delta > 0 ? '+' : '').$fmt($adjustment->delta)]) }}</p>
                        <p class="text-[11.5px] text-ink-faint">{{ $adjustment->adjustedBy?->name ?? '—' }} · <span class="hrm-num">{{ $adjustment->created_at?->format('d.m.Y H:i') }}</span></p>
                        <p class="mt-0.5 text-[12px] text-ink-muted">{{ $adjustment->reason }}</p>
                    </li>
                @endforeach
                @foreach ($card->events as $event)
                    <li wire:key="event-{{ $event->id }}" class="relative border-l border-hairline pb-3 pl-4 last:pb-0">
                        <span class="absolute -left-[4.5px] top-1.5 h-2 w-2 rounded-full {{ $cardBadge[$event->to_status][1] ?? 'bg-zinc-400' }}"></span>
                        <p class="text-[12.5px] font-medium text-ink">{{ __($t.'.events.'.$event->action) }}</p>
                        <p class="text-[11.5px] text-ink-faint">{{ $event->user?->name ?? __($t.'.system') }} · <span class="hrm-num">{{ $event->created_at?->format('d.m.Y H:i') }}</span></p>
                        @if ($event->reason)
                            <p class="mt-0.5 text-[12px] text-ink-muted">{{ $event->action === 'closed_early' ? __($t.'.closure_reasons.'.$event->reason) : $event->reason }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</div>
