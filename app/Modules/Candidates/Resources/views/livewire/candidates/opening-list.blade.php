@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        @include('candidates::livewire.candidates.partials.recruitment-panel', [
            'panelTitle' => __('candidates::recruitment.titles.openings'),
            'panelSubtitle' => $num($this->openingRows->total()).' '.__('candidates::recruitment.labels.openings_unit'),
        ])
    @endteleport

    <div class="lg:hidden">@include('candidates::livewire.candidates.partials.recruitment-nav')</div>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('candidates::recruitment.titles.openings')"
        :breadcrumb="__('candidates::common.titles.candidates')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($this->totalOpenings)" :label="__('candidates::recruitment.titles.openings')" />
            <x-page-header.stat :value="$num($this->activeApplications)" :label="__('candidates::recruitment.labels.applications')" tone="blue" />
            <x-page-header.stat :value="$num($this->totalPublished)" :label="__('candidates::recruitment.labels.published_at')" tone="green" />
        </x-slot:stats>

        <x-slot:actions>
            @can('create', App\Models\Candidate::class)
                <x-pill-button variant="primary" wire:click="openSideMenu('add-opening')">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('candidates::recruitment.actions.add_opening') }}
                </x-pill-button>
            @endcan
        </x-slot:actions>

        @include('candidates::livewire.candidates.partials.recruitment-toolbar', [
            'statusOptions' => ['draft', 'open', 'closed', 'cancelled'],
        ])
    </x-page-header>

    {{-- ===================== table ===================== --}}
    <x-table.tbl :headers="[
        __('candidates::recruitment.labels.title'),
        __('candidates::recruitment.labels.requisition'),
        __('candidates::recruitment.labels.structure'),
        __('candidates::recruitment.labels.pack_summary'),
        __('candidates::recruitment.labels.applications_count'),
        __('candidates::recruitment.labels.timeline'),
        __('personnel::common.labels.action'),
    ]">
        @forelse ($this->openingRows as $opening)
            <tr wire:key="opening-row-{{ $opening->id }}">
                <x-table.td standart-width>
                    <div class="max-w-[240px] leading-tight">
                        <a href="{{ route('candidates.openings.show', $opening) }}" wire:navigate
                            class="block truncate text-[13px] font-semibold text-ink transition hover:underline">{{ $opening->title }}</a>
                        @if ($opening->note)
                            <p class="line-clamp-2 text-[11px] text-ink-faint">{{ $opening->note }}</p>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[180px] leading-tight">
                        <p class="truncate text-[12.5px] text-ink-soft">{{ $opening->requisition?->title ?? '—' }}</p>
                        <p class="truncate text-[11px] text-ink-faint">{{ $this->recruitmentStatusLabel($opening->requisition?->status) }}</p>
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[180px] leading-tight">
                        <p class="truncate text-[12.5px] text-ink-soft">{{ $opening->structure?->name ?? '—' }}</p>
                        <p class="truncate text-[11px] text-ink-faint">{{ $opening->position?->name ?? '—' }}</p>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col items-start gap-1.5">
                        <x-small-badge mode="secondary">{{ $this->recruitmentPackLabel($opening->profile_pack) }}</x-small-badge>
                        <x-small-badge :mode="$this->recruitmentStatusTone($opening->status)" dot>{{ $this->recruitmentStatusLabel($opening->status) }}</x-small-badge>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="hrm-num leading-tight">
                        <p class="text-[13px] font-semibold text-ink">{{ $opening->applications_count }}</p>
                        <p class="text-[11px] text-ink-faint">{{ $opening->headcount }} {{ __('candidates::recruitment.labels.headcount_short') }}</p>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="hrm-num leading-tight">
                        <p class="text-[12.5px] text-ink-soft">{{ optional($opening->published_at)->format('d.m.Y') ?? '—' }}</p>
                        <p class="text-[11px] text-ink-faint">{{ optional($opening->closes_at)->format('d.m.Y') ?? '—' }}</p>
                    </div>
                </x-table.td>

                <x-table.td :isButton="true">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('candidates.openings.show', $opening) }}" wire:navigate
                            title="{{ __('candidates::recruitment.actions.open_detail') }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                        @can('update', App\Models\Candidate::class)
                            <button type="button" wire:click="openSideMenu('edit-opening', {{ $opening->id }})"
                                title="{{ __('candidates::recruitment.actions.edit') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                <x-icons.edit-icon color="text-current" hover="text-current" />
                            </button>
                        @endcan
                    </div>
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="7">{{ __('candidates::recruitment.empty.openings') }}</x-table.empty>
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$this->openingRows" :unit="__('candidates::recruitment.labels.openings_unit')" />

    <x-side-modal>
        @can('create', App\Models\Candidate::class)
            @if ($showSideMenu === 'add-opening')
                <livewire:candidates.add-opening wire:key="candidate-add-opening-modal" lazy />
            @endif
        @endcan

        @can('update', App\Models\Candidate::class)
            @if ($showSideMenu === 'edit-opening')
                <livewire:candidates.edit-opening :openingModel="$modelName" :key="'candidate-edit-opening-modal-'.$modelName" />
            @endif
        @endcan
    </x-side-modal>
</div>
