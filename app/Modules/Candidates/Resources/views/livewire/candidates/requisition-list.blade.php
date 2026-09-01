@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        @include('candidates::livewire.candidates.partials.recruitment-panel', [
            'panelTitle' => __('candidates::recruitment.titles.requisitions'),
            'panelSubtitle' => $num($this->requisitionRows->total()).' '.__('candidates::recruitment.labels.requisitions_unit'),
        ])
    @endteleport

    <div class="lg:hidden">@include('candidates::livewire.candidates.partials.recruitment-nav')</div>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('candidates::recruitment.titles.requisitions')"
        :breadcrumb="__('candidates::common.titles.candidates')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($this->draftCount)" :label="__('candidates::recruitment.statuses.draft')" />
            <x-page-header.stat :value="$num($this->openCount)" :label="__('candidates::recruitment.statuses.open')" tone="green" />
            <x-page-header.stat :value="$num($this->totalHeadcount)" :label="__('candidates::recruitment.labels.headcount_short')" tone="blue" />
        </x-slot:stats>

        <x-slot:actions>
            @can('create', App\Models\Candidate::class)
                <x-pill-button variant="primary" wire:click="openSideMenu('add-requisition')">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('candidates::recruitment.actions.add_requisition') }}
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
        __('candidates::recruitment.labels.structure'),
        __('candidates::recruitment.labels.pack_summary'),
        __('candidates::recruitment.labels.owner_summary'),
        __('candidates::recruitment.labels.timeline'),
        __('candidates::recruitment.labels.openings_count'),
        __('personnel::common.labels.action'),
    ]">
        @forelse ($this->requisitionRows as $requisition)
            <tr wire:key="requisition-row-{{ $requisition->id }}">
                <x-table.td standart-width>
                    <div class="max-w-[260px] leading-tight">
                        <a href="{{ route('candidates.requisitions.show', $requisition) }}" wire:navigate
                            class="block truncate text-[13px] font-semibold text-ink transition hover:underline">{{ $requisition->title }}</a>
                        @if ($requisition->hiring_reason)
                            <p class="truncate text-[11px] text-ink-faint">{{ $requisition->hiring_reason }}</p>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[200px] leading-tight">
                        <p class="truncate text-[12.5px] text-ink-soft">{{ $requisition->structure?->name ?? '—' }}</p>
                        <p class="truncate text-[11px] text-ink-faint">{{ $requisition->position?->name ?? '—' }}</p>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col items-start gap-1.5">
                        <x-small-badge mode="secondary">{{ $this->recruitmentPackLabel($requisition->profile_pack) }}</x-small-badge>
                        <x-small-badge :mode="$this->recruitmentStatusTone($requisition->status)" dot>{{ $this->recruitmentStatusLabel($requisition->status) }}</x-small-badge>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="leading-tight">
                        <p class="text-[12.5px] text-ink">{{ $requisition->owner?->name ?? '—' }}</p>
                        <p class="text-[11px] text-ink-faint">{{ $requisition->requester?->name ?? '—' }}</p>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="hrm-num leading-tight">
                        <p class="text-[12.5px] text-ink-soft">{{ optional($requisition->opens_at)->format('d.m.Y') ?? '—' }}</p>
                        <p class="text-[11px] text-ink-faint">{{ optional($requisition->closes_at)->format('d.m.Y') ?? '—' }}</p>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="hrm-num leading-tight">
                        <p class="text-[13px] font-semibold text-ink">{{ $requisition->openings_count }}</p>
                        <p class="text-[11px] text-ink-faint">{{ $requisition->headcount }} {{ __('candidates::recruitment.labels.headcount_short') }}</p>
                    </div>
                </x-table.td>

                <x-table.td :isButton="true">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('candidates.requisitions.show', $requisition) }}" wire:navigate
                            title="{{ __('candidates::recruitment.actions.open_detail') }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                        @can('update', App\Models\Candidate::class)
                            <button type="button" wire:click="openSideMenu('edit-requisition', {{ $requisition->id }})"
                                title="{{ __('candidates::recruitment.actions.edit') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                <x-icons.edit-icon color="text-current" hover="text-current" />
                            </button>
                        @endcan
                    </div>
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="7">{{ __('candidates::recruitment.empty.requisitions') }}</x-table.empty>
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$this->requisitionRows" :unit="__('candidates::recruitment.labels.requisitions_unit')" />

    <x-side-modal>
        @can('create', App\Models\Candidate::class)
            @if ($showSideMenu === 'add-requisition')
                <livewire:candidates.add-requisition wire:key="candidate-add-requisition-modal" lazy />
            @endif
        @endcan

        @can('update', App\Models\Candidate::class)
            @if ($showSideMenu === 'edit-requisition')
                <livewire:candidates.edit-requisition :requisitionModel="$modelName" :key="'candidate-edit-requisition-modal-'.$modelName" />
            @endif
        @endcan
    </x-side-modal>
</div>
