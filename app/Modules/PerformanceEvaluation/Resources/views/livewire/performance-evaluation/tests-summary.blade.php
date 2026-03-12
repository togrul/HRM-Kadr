<div class="space-y-4">
    <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_test_banks')" icon="icons.training-icon">
        <div class="space-y-3">
            @forelse ($this->recentTestBanks as $bank)
                <x-ui.list-card>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-zinc-900">{{ $bank->name }}</span>
                        <x-small-badge :mode="$bank->is_active ? 'green' : 'red'">{{ $bank->is_active ? __('performance_evaluation::dashboard.labels.active') : __('performance_evaluation::dashboard.labels.inactive') }}</x-small-badge>
                    </div>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.labels.questions_count', ['count' => $bank->questions_count]) }} • {{ __('performance_evaluation::dashboard.labels.pass_score_value', ['score' => $bank->pass_score]) }}</p>
                </x-ui.list-card>
            @empty
                <x-ui.empty-state icon="icons.training-icon" :message="__('performance_evaluation::dashboard.empty.test_banks')" />
            @endforelse
        </div>
    </x-surface-card>

    <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_test_attempts')" icon="icons.pending-icon">
        <div class="space-y-3">
            @forelse ($this->recentTestAttempts as $attempt)
                <x-ui.list-card>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-zinc-900">#{{ $attempt->id }} • {{ $attempt->session?->bank?->name ?? '-' }}</span>
                        <x-small-badge :mode="$attempt->status === 'completed' ? 'green' : ($attempt->status === 'review_pending' ? 'red' : 'secondary')">
                            {{ __('performance_evaluation::dashboard.test_statuses.'.$attempt->status) }}
                        </x-small-badge>
                    </div>
                    <p class="mt-1 text-xs text-zinc-500">{{ $attempt->session?->personnel?->fullname ?? '-' }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.labels.score_with_percentage', ['score' => $attempt->score ?? '—', 'percentage' => $attempt->percentage ?? '—']) }}</p>
                </x-ui.list-card>
            @empty
                <x-ui.empty-state icon="icons.pending-icon" :message="__('performance_evaluation::dashboard.empty.test_attempts')" />
            @endforelse
        </div>
    </x-surface-card>

    <x-surface-card :title="__('performance_evaluation::dashboard.cards.pending_review_answers')" icon="icons.profile-outline-icon">
        <div class="space-y-3">
            @forelse ($this->pendingReviewAnswers as $answer)
                <x-ui.list-card tone="amber">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-zinc-900">#{{ $answer->attempt?->id }} • {{ __('performance_evaluation::dashboard.question_types.'.$answer->question?->question_type) }}</span>
                        <x-small-badge mode="amber">{{ __('performance_evaluation::dashboard.labels.pending_review') }}</x-small-badge>
                    </div>
                    <p class="mt-1 text-xs text-zinc-500">{{ $answer->attempt?->session?->personnel?->fullname ?? '-' }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($answer->question?->prompt, 110) }}</p>
                </x-ui.list-card>
            @empty
                <x-ui.empty-state icon="icons.comment-icon" :message="__('performance_evaluation::dashboard.empty.pending_review_answers')" />
            @endforelse
        </div>
    </x-surface-card>
</div>
