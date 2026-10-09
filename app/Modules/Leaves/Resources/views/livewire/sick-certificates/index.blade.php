@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $stats = $this->stats;
    $staleAfterDays = $this->staleAfterDays;
    $canCreate = auth()->user()?->can('create', App\Models\LeaveSickCertificate::class) === true;
    $canUpdate = auth()->user()?->can('update', App\Models\LeaveSickCertificate::class) === true;
    $statusOptions = collect([['id' => '', 'label' => __('leaves::sick_certificates.filters.all').' · '.$num($stats['total'])]])
        ->concat(collect(App\Models\LeaveSickCertificate::STATUSES)->map(fn (string $status): array => [
            'id' => $status,
            'label' => __('leaves::sick_certificates.statuses.'.$status).' · '.$num($stats[$status] ?? 0),
        ]))
        ->all();
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('leaves::common.titles.leaves')"
            :subtitle="$num($stats['total']).' '.__('leaves::common.labels.unit')"
        >
            @include('leaves::partials.module-nav', ['active' => 'sick_certificates'])
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        collapsible-filters
        :filters-active="$this->hasActiveFilters"
        :title="__('leaves::sick_certificates.title')"
        :breadcrumb="__('leaves::common.titles.leaves')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 11v6M9 14h6"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($stats['total'])" :label="__('leaves::sick_certificates.stats.total')" />
            <x-page-header.stat :value="$num($stats['open'])" :label="__('leaves::sick_certificates.stats.open')" tone="amber" />
            <x-page-header.stat :value="$num($stats['closed'])" :label="__('leaves::sick_certificates.stats.closed')" />
            <x-page-header.stat :value="$num($stats['days'])" :label="__('leaves::sick_certificates.stats.days')" />
        </x-slot:stats>

        <x-slot:actions>
            @can('export', App\Models\LeaveSickCertificate::class)
                <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                    title="{{ __('leaves::sick_certificates.actions.export_excel') }}">
                    <x-icons.excel-icon />
                </x-pill-button>
            @endcan
            @if ($canCreate)
                <x-pill-button variant="primary" x-on:click="$dispatch('sick-certificate-editor:open', { mode: 'create' })">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('leaves::sick_certificates.actions.new') }}
                </x-pill-button>
            @endif
        </x-slot:actions>

        {{-- toolbar --}}
        <div class="flex flex-col gap-2.5">
            <div class="flex flex-wrap items-end gap-3">
                <label class="w-full flex-1 sm:min-w-[220px] sm:max-w-[300px]">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('leaves::sick_certificates.labels.employee') }}</span>
                    <x-livewire-input mode="gray" name="fullname" wire:model.live.debounce.400ms="fullname"
                        placeholder="{{ __('leaves::sick_certificates.filters.person_placeholder') }}" />
                </label>

                <label class="w-full sm:w-44">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('leaves::sick_certificates.labels.number') }}</span>
                    <x-livewire-input mode="gray" name="number" wire:model.live.debounce.400ms="number"
                        placeholder="{{ __('leaves::sick_certificates.filters.number_placeholder') }}" />
                </label>

                <div class="shrink-0">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('leaves::sick_certificates.labels.period') }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-[150px] shrink-0">
                            <x-ui.date-input wire:model.live="from" aria-label="{{ __('leaves::sick_certificates.labels.starts_at') }}" />
                        </div>
                        <span class="shrink-0 text-ink-faint">&ndash;</span>
                        <div class="w-[150px] shrink-0">
                            <x-ui.date-input wire:model.live="to" aria-label="{{ __('leaves::sick_certificates.labels.ends_at') }}" />
                        </div>
                    </div>
                </div>

                <label class="min-w-[170px] flex-1">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('leaves::sick_certificates.labels.medical_institution') }}</span>
                    <x-livewire-input mode="gray" name="institution" wire:model.live.debounce.400ms="institution"
                        placeholder="{{ __('leaves::sick_certificates.filters.institution_placeholder') }}" />
                </label>

                <x-ui.select-dropdown
                    :aria-label="__('leaves::sick_certificates.filters.status')"
                    wire:key="sick-certificates-status-filter"
                    :placeholder="__('leaves::sick_certificates.filters.all')"
                    :clearable="false"
                    mode="gray"
                    class="w-full sm:w-48 [&>div]:mt-0"
                    wire:model.live="status"
                    :model="$statusOptions"
                />

                <x-filter.reset :active="$this->hasActiveFilters" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="hrm-eyebrow">{{ __('leaves::sick_certificates.filters.status') }}</span>
                <x-filter.nav wrap class="min-w-0">
                    <x-filter.item wire:click.prevent="setStatus('')" :active="$status === '' && ! $stale">{{ __('leaves::sick_certificates.filters.all') }}</x-filter.item>
                    <x-filter.item wire:click.prevent="setStatus('open')" :active="$status === 'open' && ! $stale">{{ __('leaves::sick_certificates.statuses.open') }}</x-filter.item>
                    <x-filter.item wire:click.prevent="setStatus('closed')" :active="$status === 'closed'">{{ __('leaves::sick_certificates.statuses.closed') }}</x-filter.item>
                    <x-filter.item wire:click.prevent="$set('stale', true)" :active="$stale">{{ __('leaves::sick_certificates.filters.stale', ['days' => $staleAfterDays]) }}</x-filter.item>
                </x-filter.nav>
            </div>
        </div>
    </x-page-header>

    <x-table.tbl sticky :headers="$this->headers()">
        @forelse ($certificates as $certificate)
            @php $person = $certificate->leave?->personnel; @endphp
            <tr wire:key="sick-certificate-row-{{ $certificate->id }}" @class(['bg-[#fafafa] text-ink-faint' => $certificate->status === 'cancelled'])>
                <x-table.td standart-width>
                    <div class="flex items-center gap-2.5">
                        <x-avatar :name="(string) $person?->fullname" :tone="$certificate->status === 'open' ? 'amber' : 'neutral'" />
                        <div class="min-w-0 max-w-[240px] leading-tight">
                            @if ($person && \Illuminate\Support\Facades\Route::has('personnel.show'))
                                <a href="{{ route('personnel.show', ['personnel' => $person->id, 'section' => 'sick']) }}" wire:navigate class="block truncate text-[13px] font-medium text-ink transition hover:underline">{{ $person->fullname }}</a>
                            @else
                                <p class="truncate text-[13px] font-medium text-ink">{{ $person?->fullname ?? $certificate->tabel_no }}</p>
                            @endif
                            <p class="truncate text-[11px] text-ink-faint">{{ $person?->position?->name }}</p>
                            <p class="hrm-num truncate text-[11px] text-ink-faint">{{ __('leaves::sick_certificates.labels.tabel_no') }} {{ $certificate->tabel_no }}</p>
                        </div>
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[200px] leading-tight">
                        <p class="hrm-num text-[13px] font-medium text-ink">№ {{ $certificate->fullNumber() }}</p>
                        @if ($certificate->continuationOf)
                            <div class="mt-1"><x-small-badge mode="blue">{{ __('leaves::sick_certificates.labels.continuation_badge', ['number' => $certificate->continuationOf->fullNumber()]) }}</x-small-badge></div>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    @include('leaves::livewire.sick-certificates.partials.period', ['certificate' => $certificate])
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[220px] leading-tight">
                        <p class="truncate text-[12.5px] text-ink-muted" title="{{ $certificate->medical_institution }}">{{ $certificate->medical_institution ?: '—' }}</p>
                        @if (filled($certificate->doctor_name))
                            <p class="truncate text-[11px] text-ink-faint">{{ $certificate->doctor_name }}</p>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td>
                    @include('leaves::livewire.sick-certificates.partials.status-badge', ['certificate' => $certificate, 'staleAfterDays' => $staleAfterDays])
                </x-table.td>

                <x-table.td :isButton="true">
                    @include('leaves::livewire.sick-certificates.partials.row-actions', ['certificate' => $certificate, 'canUpdate' => $canUpdate, 'canCreate' => $canCreate])
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="count($this->headers())" :filtered="$this->hasActiveFilters" :hint="__('leaves::sick_certificates.empty.hint')">
                {{ __('leaves::sick_certificates.empty.title') }}
                <x-slot:action>
                    @if ($canCreate)
                        <x-pill-button variant="primary" x-on:click="$dispatch('sick-certificate-editor:open', { mode: 'create' })">{{ __('leaves::sick_certificates.actions.new') }}</x-pill-button>
                    @endif
                </x-slot:action>
            </x-table.empty>
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$certificates" :unit="__('leaves::common.labels.unit')" />

    <livewire:leaves.sick-certificate-editor wire:key="sick-certificate-editor" />
</div>
