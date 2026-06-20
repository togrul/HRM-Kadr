<section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                {{ __('candidates::recruitment.titles.ats_completion') }}
            </div>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
                {{ __('candidates::recruitment.titles.interviews_offers_pool') }}
            </h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                {{ __('candidates::recruitment.labels.ats_completion_note') }}
            </p>
        </div>
    </div>

    <div class="mt-6 grid gap-5 2xl:grid-cols-2">
        <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">{{ __('candidates::recruitment.titles.interviews') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('candidates::recruitment.labels.interviews_note') }}</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm">{{ $application->interviews->count() }}</span>
            </div>

            <form wire:submit="scheduleInterview" class="mt-5 grid gap-3 sm:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.interviewer') }}</span>
                    <select wire:model="interviewForm.interviewer_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                        <option value="">---</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('interviewForm.interviewer_id') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.scheduled_at') }}</span>
                    <input type="datetime-local" wire:model="interviewForm.scheduled_at" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('interviewForm.scheduled_at') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.duration_minutes') }}</span>
                    <input type="number" min="15" max="240" wire:model="interviewForm.duration_minutes" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('interviewForm.duration_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.location') }}</span>
                    <input type="text" wire:model="interviewForm.location" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('interviewForm.location') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <label class="space-y-2 sm:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.note') }}</span>
                    <textarea rows="3" wire:model="interviewForm.notes" class="w-full rounded-2xl border-0 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900"></textarea>
                    @error('interviewForm.notes') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <div class="sm:col-span-2">
                    <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(15,23,42,0.8)] transition hover:bg-slate-800">
                        {{ __('candidates::recruitment.actions.schedule_interview') }}
                    </button>
                </div>
            </form>

            <div class="mt-5 space-y-3">
                @forelse ($application->interviews as $interview)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-950">{{ $interview->interviewer?->name ?? __('candidates::recruitment.labels.unassigned') }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">{{ optional($interview->scheduled_at)->format('d.m.Y H:i') ?? '—' }} · {{ $interview->duration_minutes }} {{ __('candidates::recruitment.labels.minutes') }}</div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-600">{{ __('candidates::recruitment.ats_statuses.'.$interview->status) }}</span>
                        </div>
                        @if ($interview->score !== null)
                            <div class="mt-3 text-sm font-semibold text-slate-700">{{ __('candidates::recruitment.labels.score') }}: {{ number_format((float) $interview->score, 1) }}</div>
                        @endif
                        @if ($interview->status === 'scheduled')
                            <div class="mt-4">
                                <button type="button" wire:click="cancelInterview({{ $interview->id }})" class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 transition hover:border-rose-200 hover:bg-rose-100">
                                    {{ __('candidates::recruitment.actions.cancel_interview') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-4 text-sm font-semibold text-slate-500">
                        {{ __('candidates::recruitment.empty.interviews') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-5">
            <h3 class="text-base font-semibold text-slate-950">{{ __('candidates::recruitment.titles.scorecard') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('candidates::recruitment.labels.scorecard_note') }}</p>

            <form wire:submit="submitScorecard" class="mt-5 grid gap-3 sm:grid-cols-3">
                <label class="space-y-2 sm:col-span-3">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.interview') }}</span>
                    <select wire:model="scoreForm.interview_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                        <option value="">---</option>
                        @foreach ($application->interviews as $interview)
                            <option value="{{ $interview->id }}">{{ $interview->interviewer?->name ?? __('candidates::recruitment.labels.unassigned') }} · {{ optional($interview->scheduled_at)->format('d.m.Y H:i') ?? '—' }}</option>
                        @endforeach
                    </select>
                    @error('scoreForm.interview_id') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                @foreach (['technical', 'communication', 'culture'] as $criterion)
                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.'.$criterion.'_score') }}</span>
                        <input type="number" min="0" max="100" wire:model="scoreForm.{{ $criterion }}" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                        @error('scoreForm.'.$criterion) <x-validation>{{ $message }}</x-validation> @enderror
                    </label>
                @endforeach

                <label class="space-y-2 sm:col-span-3">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.note') }}</span>
                    <textarea rows="3" wire:model="scoreForm.note" class="w-full rounded-2xl border-0 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900"></textarea>
                    @error('scoreForm.note') <x-validation>{{ $message }}</x-validation> @enderror
                </label>

                <div class="sm:col-span-3">
                    <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(15,23,42,0.8)] transition hover:bg-slate-800">
                        {{ __('candidates::recruitment.actions.submit_scorecard') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-5">
            <h3 class="text-base font-semibold text-slate-950">{{ __('candidates::recruitment.titles.offer_management') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('candidates::recruitment.labels.offer_note') }}</p>

            <form wire:submit="createOffer" class="mt-5 grid gap-3 sm:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.salary_amount') }}</span>
                    <input type="number" min="0" step="0.01" wire:model="offerForm.salary_amount" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('offerForm.salary_amount') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.currency') }}</span>
                    <input type="text" maxlength="3" wire:model="offerForm.currency" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold uppercase text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('offerForm.currency') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.start_date') }}</span>
                    <input type="date" wire:model="offerForm.start_date" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('offerForm.start_date') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.expires_at') }}</span>
                    <input type="date" wire:model="offerForm.expires_at" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('offerForm.expires_at') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-2 sm:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.terms') }}</span>
                    <textarea rows="3" wire:model="offerForm.terms" class="w-full rounded-2xl border-0 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900"></textarea>
                    @error('offerForm.terms') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <div class="sm:col-span-2">
                    <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(15,23,42,0.8)] transition hover:bg-slate-800">
                        {{ __('candidates::recruitment.actions.send_offer') }}
                    </button>
                </div>
            </form>

            <div class="mt-5 space-y-3">
                @forelse ($application->offers as $offer)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-950">{{ $offer->salary_amount ? number_format((float) $offer->salary_amount, 2).' '.$offer->currency : __('candidates::recruitment.labels.salary_not_set') }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">{{ optional($offer->start_date)->format('d.m.Y') ?? '—' }} · {{ optional($offer->expires_at)->format('d.m.Y') ?? '—' }}</div>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase text-amber-700">{{ __('candidates::recruitment.ats_statuses.'.$offer->status) }}</span>
                        </div>
                        @if ($offer->status === 'sent')
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach (['accepted', 'declined', 'withdrawn'] as $status)
                                    <button type="button" wire:click="updateOfferStatus({{ $offer->id }}, '{{ $status }}')" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-950 hover:text-slate-950">
                                        {{ __('candidates::recruitment.actions.offer_'.$status) }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-4 text-sm font-semibold text-slate-500">
                        {{ __('candidates::recruitment.empty.offers') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-5">
            <h3 class="text-base font-semibold text-slate-950">{{ __('candidates::recruitment.titles.talent_pool') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('candidates::recruitment.labels.talent_pool_note') }}</p>

            <form wire:submit="addToTalentPool" class="mt-5 grid gap-3 sm:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.pool_name') }}</span>
                    <input type="text" wire:model="poolForm.pool_name" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('poolForm.pool_name') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.valid_until') }}</span>
                    <input type="date" wire:model="poolForm.valid_until" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900">
                    @error('poolForm.valid_until') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <label class="space-y-2 sm:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-slate-500">{{ __('candidates::recruitment.labels.note') }}</span>
                    <textarea rows="3" wire:model="poolForm.notes" class="w-full rounded-2xl border-0 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition focus:ring-2 focus:ring-slate-900"></textarea>
                    @error('poolForm.notes') <x-validation>{{ $message }}</x-validation> @enderror
                </label>
                <div class="sm:col-span-2">
                    <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(15,23,42,0.8)] transition hover:bg-slate-800">
                        {{ __('candidates::recruitment.actions.add_to_talent_pool') }}
                    </button>
                </div>
            </form>

            <div class="mt-5 space-y-3">
                @forelse ($application->talentPoolEntries as $entry)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-950">{{ $entry->pool_name }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">{{ optional($entry->valid_until)->format('d.m.Y') ?? '—' }}</div>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase text-emerald-700">{{ __('candidates::recruitment.ats_statuses.'.$entry->status) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-4 text-sm font-semibold text-slate-500">
                        {{ __('candidates::recruitment.empty.talent_pool') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>
