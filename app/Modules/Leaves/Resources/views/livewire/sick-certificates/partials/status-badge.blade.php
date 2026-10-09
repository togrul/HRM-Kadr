{{-- Status pill of one certificate. Expects $certificate and $staleAfterDays. --}}
@php
    $days = \App\Modules\Leaves\Application\Services\SickCertificateRegister::days($certificate);
    $isStale = $certificate->status === 'open' && $days > $staleAfterDays;
    $mode = match ($certificate->status) {
        'open' => $isStale ? 'rose' : 'amber',
        'closed' => 'green',
        default => 'secondary',
    };
@endphp

<div class="max-w-[190px] leading-tight">
    <x-small-badge :mode="$mode" dot>{{ __('leaves::sick_certificates.statuses.'.$certificate->status) }}</x-small-badge>
    @if ($isStale)
        <p class="mt-1 text-[11px] text-[#be123c]">{{ __('leaves::sick_certificates.labels.stale_badge') }}</p>
    @elseif ($certificate->status === 'cancelled' && filled($certificate->cancel_reason))
        <p class="mt-1 truncate text-[11px] text-ink-faint" title="{{ $certificate->cancel_reason }}">{{ $certificate->cancel_reason }}</p>
    @endif
</div>
