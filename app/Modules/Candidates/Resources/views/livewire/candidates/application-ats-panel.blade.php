<section class="rounded-2xl border border-hairline bg-white p-4 shadow-card sm:p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="hrm-eyebrow">
                {{ __('candidates::recruitment.titles.ats_completion') }}
            </div>
            <h2 class="mt-1 text-[15px] font-semibold tracking-[-0.02em] text-ink">
                {{ __('candidates::recruitment.titles.interviews_offers_pool') }}
            </h2>
            <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-ink-muted">
                {{ __('candidates::recruitment.labels.ats_completion_note') }}
            </p>
        </div>
    </div>

    @php
        $atsTabs = [
            'interviews' => [__('candidates::recruitment.titles.interviews'), $application->interviews->count()],
            'scorecard' => [__('candidates::recruitment.titles.scorecard'), null],
            'offers' => [__('candidates::recruitment.titles.offer_management'), $application->offers->count()],
            'pool' => [__('candidates::recruitment.titles.talent_pool'), $application->talentPoolEntries->count()],
        ];
    @endphp

    <x-filter.nav class="mt-4" role="tablist">
        @foreach ($atsTabs as $_key => [$_label, $_count])
            <x-filter.item wire:key="ats-tab-{{ $_key }}" role="tab" wire:click.prevent="setTab('{{ $_key }}')" :active="$tab === $_key">
                {{ $_label }}
                @if ($_count)
                    <span class="hrm-num ml-1.5 text-[12px] text-ink-faint">{{ $_count }}</span>
                @endif
            </x-filter.item>
        @endforeach
    </x-filter.nav>

    <div class="mt-4" role="tabpanel">
        @if ($tab === 'interviews')
            <div>
            <p class="text-[12.5px] text-ink-muted">{{ __('candidates::recruitment.labels.interviews_note') }}</p>

            <form wire:submit="scheduleInterview" class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.interviewer') }}</span>
                    <x-ui.select wire:model="interviewForm.interviewer_id">
                        <option value="">---</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </x-ui.select>
                    @error('interviewForm.interviewer_id') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.scheduled_at') }}</span>
                    <input type="datetime-local" wire:model="interviewForm.scheduled_at" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('interviewForm.scheduled_at') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.duration_minutes') }}</span>
                    <input type="number" min="15" max="240" wire:model="interviewForm.duration_minutes" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('interviewForm.duration_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.location') }}</span>
                    <input type="text" wire:model="interviewForm.location" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('interviewForm.location') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <label class="space-y-1 sm:col-span-2">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.note') }}</span>
                    <textarea rows="3" wire:model="interviewForm.notes" class="w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm"></textarea>
                    @error('interviewForm.notes') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <div class="sm:col-span-2">
                    <x-pill-button type="submit" variant="primary">{{ __('candidates::recruitment.actions.schedule_interview') }}</x-pill-button>
                </div>
            </form>

            <div class="mt-4 space-y-2">
                @forelse ($application->interviews as $interview)
                    <div class="rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-[13px] font-semibold text-ink">{{ $interview->interviewer?->name ?? __('candidates::recruitment.labels.unassigned') }}</div>
                                <div class="mt-1 text-[11.5px] text-ink-faint">{{ optional($interview->scheduled_at)->format('d.m.Y H:i') ?? '—' }} · {{ $interview->duration_minutes }} {{ __('candidates::recruitment.labels.minutes') }}</div>
                            </div>
                            <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase text-zinc-600">{{ __('candidates::recruitment.ats_statuses.'.$interview->status) }}</span>
                        </div>
                        @if ($interview->score !== null)
                            <div class="mt-3 text-[12.5px] text-ink-soft">{{ __('candidates::recruitment.labels.score') }}: {{ number_format((float) $interview->score, 1) }}</div>
                        @endif
                        @if ($interview->status === 'scheduled')
                            <div class="mt-4">
                                <x-pill-button variant="danger"
                                    data-title="{{ __('candidates::recruitment.actions.cancel_interview') }}"
                                    data-message="{{ __('candidates::recruitment.messages.cancel_interview_confirm') }}"
                                    x-on:click="$dispatch('confirm-action', { title: $el.dataset.title, message: $el.dataset.message, confirmText: $el.dataset.title, tone: 'rose', run: () => $wire.cancelInterview({{ $interview->id }}) })"
                                >{{ __('candidates::recruitment.actions.cancel_interview') }}</x-pill-button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-hairline bg-[#fafafa] px-4 py-6 text-center text-[12.5px] text-ink-faint">
                        {{ __('candidates::recruitment.empty.interviews') }}
                    </div>
                @endforelse
            </div>
        </div>
        @endif
        @if ($tab === 'scorecard')
            <div>
            <p class="text-[12.5px] text-ink-muted">{{ __('candidates::recruitment.labels.scorecard_note') }}</p>

            <form wire:submit="submitScorecard" class="mt-4 grid gap-3 sm:grid-cols-3">
                <label class="space-y-1 sm:col-span-3">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.interview') }}</span>
                    <x-ui.select wire:model="scoreForm.interview_id">
                        <option value="">---</option>
                        @foreach ($application->interviews as $interview)
                            <option value="{{ $interview->id }}">{{ $interview->interviewer?->name ?? __('candidates::recruitment.labels.unassigned') }} · {{ optional($interview->scheduled_at)->format('d.m.Y H:i') ?? '—' }}</option>
                        @endforeach
                    </x-ui.select>
                    @error('scoreForm.interview_id') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                @foreach (['technical', 'communication', 'culture'] as $criterion)
                    <label class="space-y-1">
                        <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.'.$criterion.'_score') }}</span>
                        <input type="number" min="0" max="100" wire:model="scoreForm.{{ $criterion }}" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                        @error('scoreForm.'.$criterion) <x-validation>{{ $message }}</x-validation> @enderror
                    </label>
                @endforeach

                <label class="space-y-1 sm:col-span-3">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.note') }}</span>
                    <textarea rows="3" wire:model="scoreForm.note" class="w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm"></textarea>
                    @error('scoreForm.note') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <div class="sm:col-span-3">
                    <x-pill-button type="submit" variant="primary">{{ __('candidates::recruitment.actions.submit_scorecard') }}</x-pill-button>
                </div>
            </form>
        </div>
        @endif
        @if ($tab === 'offers')
            <div>
            <p class="text-[12.5px] text-ink-muted">{{ __('candidates::recruitment.labels.offer_note') }}</p>

            <form wire:submit="createOffer" class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.salary_amount') }}</span>
                    <input type="number" min="0" step="0.01" wire:model="offerForm.salary_amount" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('offerForm.salary_amount') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.currency') }}</span>
                    <input type="text" maxlength="3" wire:model="offerForm.currency" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 uppercase text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('offerForm.currency') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.start_date') }}</span>
                    <input type="date" wire:model="offerForm.start_date" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('offerForm.start_date') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.expires_at') }}</span>
                    <input type="date" wire:model="offerForm.expires_at" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('offerForm.expires_at') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-1 sm:col-span-2">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.terms') }}</span>
                    <textarea rows="3" wire:model="offerForm.terms" class="w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm"></textarea>
                    @error('offerForm.terms') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <div class="sm:col-span-2">
                    <x-pill-button type="submit" variant="primary">{{ __('candidates::recruitment.actions.send_offer') }}</x-pill-button>
                </div>
            </form>

            <div class="mt-4 space-y-2">
                @forelse ($application->offers as $offer)
                    <div class="rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-[13px] font-semibold text-ink">{{ $offer->salary_amount ? number_format((float) $offer->salary_amount, 2).' '.$offer->currency : __('candidates::recruitment.labels.salary_not_set') }}</div>
                                <div class="mt-1 text-[11.5px] text-ink-faint">{{ optional($offer->start_date)->format('d.m.Y') ?? '—' }} · {{ optional($offer->expires_at)->format('d.m.Y') ?? '—' }}</div>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase text-amber-700">{{ __('candidates::recruitment.ats_statuses.'.$offer->status) }}</span>
                        </div>
                        @if ($offer->status === 'sent')
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach (['accepted', 'declined', 'withdrawn'] as $status)
                                    <x-pill-button wire:click="updateOfferStatus({{ $offer->id }}, '{{ $status }}')">{{ __('candidates::recruitment.actions.offer_'.$status) }}</x-pill-button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-hairline bg-[#fafafa] px-4 py-6 text-center text-[12.5px] text-ink-faint">
                        {{ __('candidates::recruitment.empty.offers') }}
                    </div>
                @endforelse
            </div>
        </div>
        @endif
        @if ($tab === 'pool')
            <div>
            <p class="text-[12.5px] text-ink-muted">{{ __('candidates::recruitment.labels.talent_pool_note') }}</p>

            <form wire:submit="addToTalentPool" class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.pool_name') }}</span>
                    <input type="text" wire:model="poolForm.pool_name" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('poolForm.pool_name') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-1">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.valid_until') }}</span>
                    <input type="date" wire:model="poolForm.valid_until" class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm">
                    @error('poolForm.valid_until') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-1 sm:col-span-2">
                    <span class="block text-[12px] font-medium text-ink-muted">{{ __('candidates::recruitment.labels.note') }}</span>
                    <textarea rows="3" wire:model="poolForm.notes" class="w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2 text-base text-ink focus:border-ink focus:bg-white focus:ring-0 sm:text-sm"></textarea>
                    @error('poolForm.notes') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <div class="sm:col-span-2">
                    <x-pill-button type="submit" variant="primary">{{ __('candidates::recruitment.actions.add_to_talent_pool') }}</x-pill-button>
                </div>
            </form>

            <div class="mt-4 space-y-2">
                @forelse ($application->talentPoolEntries as $entry)
                    <div class="rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-[13px] font-semibold text-ink">{{ $entry->pool_name }}</div>
                                <div class="mt-1 text-[11.5px] text-ink-faint">{{ optional($entry->valid_until)->format('d.m.Y') ?? '—' }}</div>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase text-emerald-700">{{ __('candidates::recruitment.ats_statuses.'.$entry->status) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-hairline bg-[#fafafa] px-4 py-6 text-center text-[12.5px] text-ink-faint">
                        {{ __('candidates::recruitment.empty.talent_pool') }}
                    </div>
                @endforelse
            </div>
        </div>
        @endif
    </div>
</section>
