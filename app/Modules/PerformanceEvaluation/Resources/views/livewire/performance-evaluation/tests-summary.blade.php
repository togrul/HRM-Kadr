@php
    $d = 'performance_evaluation::dashboard';
    $section = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
    $row = 'px-5 py-3 transition-colors hover:bg-[#fafafa]';
    $pill = 'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium';
@endphp

<div class="flex flex-col gap-4">
    <div class="{{ $section }}">
        <div class="{{ $sectionHead }}"><p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.recent_test_banks') }}</p></div>
        <div class="divide-y divide-hairline-subtle">
            @forelse ($this->recentTestBanks as $bank)
                <div wire:key="summary-bank-{{ $bank->id }}" class="{{ $row }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="min-w-0 truncate text-[13px] font-semibold text-ink">{{ $bank->name }}</p>
                        <span class="{{ $pill }} {{ $bank->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f4f4f5] text-ink-muted' }}"><span class="h-1.5 w-1.5 rounded-full {{ $bank->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>{{ $bank->is_active ? __($d.'.labels.active') : __($d.'.labels.inactive') }}</span>
                    </div>
                    <p class="mt-0.5 text-[12px] text-ink-muted">{{ __($d.'.labels.questions_count', ['count' => $bank->questions_count]) }} · {{ __($d.'.labels.pass_score_value', ['score' => $bank->pass_score]) }}</p>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_banks') }}</p>
            @endforelse
        </div>
    </div>

    <div class="{{ $section }}">
        <div class="{{ $sectionHead }}"><p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.recent_test_attempts') }}</p></div>
        <div class="divide-y divide-hairline-subtle">
            @forelse ($this->recentTestAttempts as $attempt)
                @php
                    $tone = match ($attempt->status) {
                        'completed' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                        'review_pending' => ['bg-rose-50 text-rose-700', 'bg-rose-500'],
                        default => ['bg-[#f4f4f5] text-ink-muted', 'bg-zinc-400'],
                    };
                @endphp
                <div wire:key="summary-attempt-{{ $attempt->id }}" class="{{ $row }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="min-w-0 truncate text-[13px] font-semibold text-ink"><span class="hrm-num text-ink-faint">#{{ $attempt->id }}</span> {{ $attempt->session?->bank?->name ?? '-' }}</p>
                        <span class="{{ $pill }} {{ $tone[0] }}"><span class="h-1.5 w-1.5 rounded-full {{ $tone[1] }}"></span>{{ __($d.'.test_statuses.'.$attempt->status) }}</span>
                    </div>
                    <p class="mt-0.5 text-[12px] text-ink-muted">{{ $attempt->session?->personnel?->fullname ?? '-' }}</p>
                    <p class="hrm-num text-[11.5px] text-ink-faint">{{ __($d.'.labels.score_with_percentage', ['score' => $attempt->score ?? '—', 'percentage' => $attempt->percentage ?? '—']) }}</p>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_attempts') }}</p>
            @endforelse
        </div>
    </div>

    <div class="{{ $section }}">
        <div class="{{ $sectionHead }}"><p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.pending_review_answers') }}</p></div>
        <div class="divide-y divide-hairline-subtle">
            @forelse ($this->pendingReviewAnswers as $answer)
                <div wire:key="summary-pending-{{ $answer->id }}" class="{{ $row }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="min-w-0 truncate text-[13px] font-semibold text-ink"><span class="hrm-num text-ink-faint">#{{ $answer->attempt?->id }}</span> {{ __($d.'.question_types.'.$answer->question?->question_type) }}</p>
                        <span class="{{ $pill }} bg-amber-50 text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ __($d.'.labels.pending_review') }}</span>
                    </div>
                    <p class="mt-0.5 text-[12px] text-ink-muted">{{ $answer->attempt?->session?->personnel?->fullname ?? '-' }}</p>
                    <p class="text-[12px] leading-5 text-ink-faint">{{ \Illuminate\Support\Str::limit($answer->question?->prompt, 110) }}</p>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.pending_review_answers') }}</p>
            @endforelse
        </div>
    </div>
</div>
