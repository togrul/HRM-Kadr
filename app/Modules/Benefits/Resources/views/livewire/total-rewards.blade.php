@php
    $rewards = $this->rewards;
    $currency = $rewards['currency'];
    $mask = '•••';
    $salaryText = $rewards['has_salary'] ? ($showSalary ? $money($rewards['salary']).' '.$currency : $mask) : '—';
@endphp

<section class="overflow-hidden rounded-xl border border-hairline bg-white">
    <div class="border-b border-hairline-subtle px-4 py-3">
        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('benefits::dashboard.rewards.title') }}</h2>
        <p class="mt-0.5 text-[12px] text-ink-faint">{{ __('benefits::dashboard.rewards.help') }}</p>
    </div>

    <div class="grid gap-3 px-4 py-4 sm:grid-cols-4">
        <div class="rounded-[10px] border border-hairline bg-[#fafafa] px-3 py-2.5">
            <p class="text-[11px] font-medium text-ink-faint">{{ __('benefits::dashboard.rewards.salary') }}</p>
            <p class="hrm-num mt-1 text-[17px] font-semibold tracking-[-0.02em] text-ink">{{ $salaryText }}</p>
        </div>
        <div class="rounded-[10px] border border-hairline bg-[#fafafa] px-3 py-2.5">
            <p class="text-[11px] font-medium text-ink-faint">{{ __('benefits::dashboard.rewards.allowances') }}</p>
            <p class="hrm-num mt-1 text-[17px] font-semibold tracking-[-0.02em] text-ink">{{ $showSalary ? $money($rewards['allowances']).' '.$currency : $mask }}</p>
        </div>
        <div class="rounded-[10px] border border-hairline bg-[#fafafa] px-3 py-2.5">
            <p class="text-[11px] font-medium text-ink-faint">{{ __('benefits::dashboard.rewards.benefits') }}</p>
            <p class="hrm-num mt-1 text-[17px] font-semibold tracking-[-0.02em] text-ink">{{ $money($rewards['benefits_employer']) }} {{ $currency }}</p>
        </div>
        <div class="rounded-[10px] border border-ink bg-ink px-3 py-2.5 text-white">
            <p class="text-[11px] font-medium text-white/70">{{ __('benefits::dashboard.rewards.total') }}</p>
            <p class="hrm-num mt-1 text-[17px] font-semibold tracking-[-0.02em]">{{ $showSalary ? $money($rewards['total']).' '.$currency : $mask }}</p>
        </div>
    </div>

    @if ($rewards['benefit_lines'] !== [])
        <div class="divide-y divide-hairline-subtle border-t border-hairline-subtle">
            @foreach ($rewards['benefit_lines'] as $line)
                <div wire:key="benefits-reward-{{ $loop->index }}" class="flex items-center justify-between gap-3 px-4 py-2.5">
                    <div class="min-w-0">
                        <p class="truncate text-[13px] font-medium text-ink">{{ $line['plan'] }}</p>
                        <p class="text-[11px] text-ink-faint">
                            {{ __('benefits::dashboard.types.'.$line['type']) }}@if ($line['provider']) · {{ $line['provider'] }}@endif
                            @if ($line['dependents'] > 0) · {{ __('benefits::dashboard.enrollments.dependents_count', ['count' => $line['dependents']]) }}@endif
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="hrm-num text-[13px] text-ink">{{ $money($line['employer']) }}</p>
                        @if ($line['employee'] > 0)
                            <p class="hrm-num text-[11px] text-ink-faint">{{ __('benefits::dashboard.rewards.employee_share', ['amount' => $money($line['employee'])]) }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($rewards['benefits_employee'] > 0)
        <p class="border-t border-hairline-subtle px-4 py-2.5 text-[12px] text-ink-muted">{{ __('benefits::dashboard.rewards.employee_total', ['amount' => $money($rewards['benefits_employee']).' '.$currency]) }}</p>
    @endif
</section>
