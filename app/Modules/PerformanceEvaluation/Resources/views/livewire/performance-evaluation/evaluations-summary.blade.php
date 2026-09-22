@php
    $d = 'performance_evaluation::dashboard';
    $iconButton = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300';
    $categoryTone = [
        'high' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'medium' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
        'weak' => ['bg-rose-50 text-rose-700', 'bg-rose-500'],
    ];
    $stage = fn (?string $status): string => $status === 'submitted' ? __($d.'.statuses.submitted') : __($d.'.statuses.draft');
@endphp

<section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
    <div class="flex flex-col gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-[13px] font-semibold text-ink">
            {{ __($d.'.cards.recent_forms') }}
            <span class="hrm-num ml-1 font-normal text-ink-faint">{{ $this->recentForms->count() }}</span>
        </p>
        <label class="flex h-9 w-full items-center gap-2 rounded-xl border border-hairline bg-white px-3 sm:w-64">
            <svg class="h-4 w-4 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" wire:model.live.debounce.300ms="formSearch" placeholder="{{ __($d.'.fields.personnel') }}…" class="h-full w-full border-0 bg-transparent p-0 text-[13px] text-ink placeholder:text-ink-faint focus:outline-none focus:ring-0">
        </label>
    </div>

    <div class="divide-y divide-hairline-subtle">
        @forelse ($this->recentForms as $form)
            <div wire:key="recent-form-{{ $form->id }}" class="group px-5 py-3.5 transition-colors hover:bg-[#fafafa]">
                <div class="flex items-start gap-3">
                    <x-avatar size="sm" :name="$form->personnel_fullname ?? '—'" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-[13.5px] font-semibold tracking-[-0.01em] text-ink">{{ $form->personnel_fullname ?? '-' }}</p>
                                <p class="truncate text-[12px] text-ink-faint">{{ $form->cycle_name }} · {{ $form->template_name }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span class="hrm-num text-[15px] font-semibold text-ink">{{ $form->final_score ?? '—' }}</span>
                                @if ($form->final_category)
                                    @php [$tone, $dot] = $categoryTone[$form->final_category] ?? $categoryTone['medium']; @endphp
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $tone }}" title="{{ __($d.'.labels.final_category') }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
                                        {{ __($d.'.categories.'.$form->final_category) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-1 text-[12px] text-ink-muted sm:grid-cols-2">
                            <p class="truncate">{{ __($d.'.evaluators.manager') }}: <span class="text-ink-soft">{{ $form->manager_name ?? __($d.'.labels.no_manager') }}</span></p>
                            <p class="truncate">{{ __($d.'.evaluators.hr') }}: <span class="text-ink-soft">{{ $form->hr_reviewer_name ?? __($d.'.labels.no_hr_reviewer') }}</span></p>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap gap-1.5 text-[11.5px]">
                                @foreach (['self' => $form->self_status, 'manager' => $form->manager_status, 'hr' => $form->hr_status] as $evaluator => $status)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-ink-muted">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $status === 'submitted' ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span>
                                        {{ __($d.'.evaluators.'.$evaluator) }}: {{ $stage($status) }}
                                    </span>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-1">
                                @can('manage-performance-evaluation')
                                    <button type="button" wire:click="relayScoreEvaluationForm({{ $form->id }})" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-hairline bg-white px-2.5 text-[12px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        {{ __($d.'.cards.score_capture') }}
                                    </button>
                                @endcan
                                <button type="button" wire:click="relayEditEvaluationForm({{ $form->id }})" class="{{ $iconButton }}" title="{{ __($d.'.actions.edit') }}" aria-label="{{ __($d.'.actions.edit') }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </button>
                                <button type="button" wire:click="relayDeleteEvaluationForm({{ $form->id }})" class="{{ $iconButton }} hover:!bg-rose-50" title="{{ __($d.'.actions.delete') }}" aria-label="{{ __($d.'.actions.delete') }}">
                                    <x-icons.delete-icon size="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="px-6 py-14 text-center text-[13px] text-ink-faint">{{ __($d.'.empty.recent_forms') }}</p>
        @endforelse
    </div>
</section>
