{{-- Period cell of one certificate. Expects $certificate. --}}
@php
    $start = filled($certificate->period_start) ? \Carbon\CarbonImmutable::parse(substr((string) $certificate->period_start, 0, 10)) : null;
    $end = filled($certificate->period_end) ? \Carbon\CarbonImmutable::parse(substr((string) $certificate->period_end, 0, 10)) : null;
    $days = \App\Modules\Leaves\Application\Services\SickCertificateRegister::days($certificate);
@endphp

<div class="leading-tight">
    <p class="hrm-num text-[13px] font-medium text-ink">
        {{ $start?->format('d.m.Y') }} &ndash; {{ $end?->format('d.m.Y') ?? __('leaves::sick_certificates.labels.open_end') }}
    </p>
    <div class="mt-1">
        <x-small-badge mode="secondary">
            {{ $end === null && $certificate->status === 'open'
                ? __('leaves::sick_certificates.labels.days_so_far', ['days' => $days])
                : __('leaves::sick_certificates.labels.days_count', ['days' => $days]) }}
        </x-small-badge>
    </div>
</div>
