@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $stats = $this->stats;
    $staleAfterDays = app(\App\Modules\Leaves\Application\Services\SickCertificateSettings::class)->staleAfterDays();
    $canCreate = auth()->user()?->can('create', App\Models\LeaveSickCertificate::class) === true;
    $canUpdate = auth()->user()?->can('update', App\Models\LeaveSickCertificate::class) === true;
@endphp

<section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline-subtle px-5 py-3">
        <div class="min-w-0">
            <h2 class="text-[15px] font-semibold tracking-[-0.02em] text-ink">{{ __('leaves::sick_certificates.title') }}</h2>
            <p class="mt-0.5 text-[11.5px] text-ink-faint">
                {{ __('leaves::sick_certificates.stats.total') }} {{ $num($stats['total']) }}
                · {{ __('leaves::sick_certificates.stats.open') }} {{ $num($stats['open']) }}
                · {{ __('leaves::sick_certificates.stats.closed') }} {{ $num($stats['closed']) }}
                · {{ __('leaves::sick_certificates.stats.days') }} {{ $num($stats['days']) }}
            </p>
        </div>
        @if ($canCreate)
            <x-pill-button variant="primary" x-on:click="$dispatch('sick-certificate-editor:open', { mode: 'create' })">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('leaves::sick_certificates.actions.new') }}
            </x-pill-button>
        @endif
    </div>

    <div class="divide-y divide-hairline-subtle">
        @forelse ($this->certificates as $certificate)
            <div wire:key="personnel-sick-certificate-{{ $certificate->id }}" @class(['grid grid-cols-1 items-center gap-3 px-5 py-3 sm:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1fr)_auto_auto]', 'bg-[#fafafa]' => $certificate->status === 'cancelled'])>
                <div class="min-w-0 leading-tight">
                    <p class="hrm-num text-[13px] font-medium text-ink">№ {{ $certificate->fullNumber() }}</p>
                    @if ($certificate->continuationOf)
                        <p class="mt-0.5 truncate text-[11px] text-ink-faint">{{ __('leaves::sick_certificates.labels.continuation_badge', ['number' => $certificate->continuationOf->fullNumber()]) }}</p>
                    @endif
                </div>
                @include('leaves::livewire.sick-certificates.partials.period', ['certificate' => $certificate])
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-[12.5px] text-ink-muted">{{ $certificate->medical_institution ?: '—' }}</p>
                    @if (filled($certificate->doctor_name))
                        <p class="truncate text-[11px] text-ink-faint">{{ $certificate->doctor_name }}</p>
                    @endif
                </div>
                @include('leaves::livewire.sick-certificates.partials.status-badge', ['certificate' => $certificate, 'staleAfterDays' => $staleAfterDays])
                @include('leaves::livewire.sick-certificates.partials.row-actions', ['certificate' => $certificate, 'canUpdate' => $canUpdate, 'canCreate' => $canCreate])
            </div>
        @empty
            <div class="px-5 py-4">
                <x-empty-inline>{{ __('leaves::sick_certificates.empty.personnel') }}</x-empty-inline>
            </div>
        @endforelse
    </div>

    <livewire:leaves.sick-certificate-editor :tabel-no="$tabelNo" :key="'personnel-sick-certificate-editor-'.$tabelNo" />
</section>
