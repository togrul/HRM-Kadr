<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        @include('candidates::livewire.candidates.partials.recruitment-panel', [
            'panelTitle' => __('candidates::recruitment.titles.requisition_detail'),
            'panelSubtitle' => $requisition->title,
        ])
    @endteleport

    <div class="lg:hidden">@include('candidates::livewire.candidates.partials.recruitment-nav')</div>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="$requisition->title"
        :breadcrumb="__('candidates::recruitment.titles.requisitions')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-pill-button :href="route('candidates.openings')" wire:navigate>
                {{ __('candidates::recruitment.actions.open_openings') }}
            </x-pill-button>
        </x-slot:actions>

        <div class="flex flex-wrap items-center gap-2">
            <x-small-badge mode="secondary">{{ $this->recruitmentPackLabel($requisition->profile_pack) }}</x-small-badge>
            <x-small-badge :mode="$this->recruitmentStatusTone($requisition->status)" dot>{{ $this->recruitmentStatusLabel($requisition->status) }}</x-small-badge>
            <x-small-badge mode="blue">{{ $requisition->headcount }} {{ __('candidates::recruitment.labels.headcount_short') }}</x-small-badge>
        </div>
    </x-page-header>

    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-fact-tile
                :label="__('candidates::recruitment.labels.structure')"
                :value="$requisition->structure?->name ?? '—'"
                :note="$requisition->position?->name ?? '—'"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.owner_summary')"
                :value="$requisition->owner?->name ?? '—'"
                :note="$requisition->requester?->name ?? '—'"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.timeline')"
                :value="optional($requisition->opens_at)->format('d.m.Y') ?? '—'"
                :note="optional($requisition->closes_at)->format('d.m.Y') ?? '—'"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.approval_status')"
                :value="__('candidates::recruitment.approval_statuses.'.($requisition->approval_status ?: 'draft'))"
                :note="match ($requisition->approval_status) {
                    'approved' => ($requisition->approver?->name ?? '—').' · '.(optional($requisition->approved_at)->format('d.m.Y H:i') ?? '—'),
                    'rejected' => ($requisition->rejecter?->name ?? '—').' · '.(optional($requisition->rejected_at)->format('d.m.Y H:i') ?? '—'),
                    default => __('candidates::recruitment.labels.awaiting_approval'),
                }"
                :tone="match ($requisition->approval_status) { 'approved' => 'green', 'rejected' => 'rose', default => 'amber' }"
            />
        </div>

        <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.requisition_approval') }}</h2>
            </div>
            <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0 flex-1">
                    <x-label for="approvalNote">{{ __('candidates::recruitment.labels.approval_note') }}</x-label>
                    <textarea wire:model="approvalNote" rows="2"
                        class="mt-1 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2 text-[12.5px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
                        placeholder="{{ __('candidates::recruitment.labels.approval_note') }}"></textarea>
                    @error('approvalNote') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if (! in_array($requisition->approval_status, ['pending', 'approved'], true))
                        <x-pill-button wire:click="submitForApproval">{{ __('candidates::recruitment.actions.submit_for_approval') }}</x-pill-button>
                    @endif
                    @if ($requisition->approval_status !== 'approved')
                        <x-pill-button variant="primary" wire:click="approve">{{ __('candidates::recruitment.actions.approve_requisition') }}</x-pill-button>
                    @endif
                    @if ($requisition->approval_status !== 'rejected')
                        <x-pill-button variant="danger" wire:click="reject">{{ __('candidates::recruitment.actions.reject_requisition') }}</x-pill-button>
                    @endif
                </div>
            </div>
            @if ($requisition->approval_note)
                <p class="border-t border-hairline-subtle bg-[#fafafa] px-4 py-3 text-[12.5px] leading-relaxed text-ink-muted">{{ $requisition->approval_note }}</p>
            @endif
        </section>

        @if ($requisition->note)
            <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="hrm-eyebrow">{{ __('candidates::recruitment.labels.note') }}</h2>
                </div>
                <p class="px-4 py-3 text-[12.5px] leading-relaxed text-ink-muted">{{ $requisition->note }}</p>
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.openings') }}</h2>
            </div>
            <div class="grid gap-2 p-3 lg:grid-cols-2">
                @forelse ($requisition->openings as $opening)
                    <a href="{{ route('candidates.openings.show', $opening) }}" wire:navigate
                        class="rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3 transition hover:border-zinc-300 hover:bg-white">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-small-badge :mode="$this->recruitmentStatusTone($opening->status)" dot>{{ $this->recruitmentStatusLabel($opening->status) }}</x-small-badge>
                            <x-small-badge mode="blue">{{ (int) ($opening->getAttributes()['applications_count'] ?? 0) }} {{ __('candidates::recruitment.labels.applications') }}</x-small-badge>
                        </div>
                        <h3 class="mt-2 truncate text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ $opening->title }}</h3>
                        <p class="mt-0.5 truncate text-[11.5px] text-ink-faint">{{ $opening->structure?->name ?? '—' }} · {{ $opening->position?->name ?? '—' }}</p>
                        <div class="hrm-num mt-2 flex items-center justify-between text-[11px] text-ink-faint">
                            <span>{{ optional($opening->published_at)->format('d.m.Y') ?? '—' }}</span>
                            <span>{{ optional($opening->closes_at)->format('d.m.Y') ?? '—' }}</span>
                        </div>
                    </a>
                @empty
                    <p class="rounded-xl border border-dashed border-hairline bg-[#fafafa] px-4 py-6 text-center text-[12.5px] text-ink-faint lg:col-span-2">
                        {{ __('candidates::recruitment.empty.openings') }}
                    </p>
                @endforelse
            </div>
        </section>
    </div>
</div>
