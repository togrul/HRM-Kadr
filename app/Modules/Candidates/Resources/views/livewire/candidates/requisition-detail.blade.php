<div class="flex flex-col gap-6 px-6 py-4">
    @include('candidates::livewire.candidates.partials.recruitment-nav')

    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex flex-col gap-5 border-b border-slate-200 pb-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.requisition_detail') }}
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $requisition->title }}</h1>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $this->recruitmentPackLabel($requisition->profile_pack) }}</span>
                    <span class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ $this->recruitmentStatusLabel($requisition->status) }}</span>
                    <span class="inline-flex rounded-full bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700">{{ $requisition->headcount }} {{ __('candidates::recruitment.labels.headcount_short') }}</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('candidates.openings') }}" class="inline-flex h-11 items-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                    {{ __('candidates::recruitment.actions.open_openings') }}
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-4">
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.structure') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $requisition->structure?->name ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $requisition->position?->name ?? '—' }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.owner_summary') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $requisition->owner?->name ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $requisition->requester?->name ?? '—' }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.timeline') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ optional($requisition->opens_at)->format('d.m.Y') ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ optional($requisition->closes_at)->format('d.m.Y') ?? '—' }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.approval_status') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ __('candidates::recruitment.approval_statuses.'.($requisition->approval_status ?: 'draft')) }}</div>
                <div class="mt-1 text-sm text-slate-500">
                    @if ($requisition->approval_status === 'approved')
                        {{ $requisition->approver?->name ?? '—' }} · {{ optional($requisition->approved_at)->format('d.m.Y H:i') ?? '—' }}
                    @elseif ($requisition->approval_status === 'rejected')
                        {{ $requisition->rejecter?->name ?? '—' }} · {{ optional($requisition->rejected_at)->format('d.m.Y H:i') ?? '—' }}
                    @else
                        {{ __('candidates::recruitment.labels.awaiting_approval') }}
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-[24px] border border-slate-200 bg-slate-50/80 p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex-1 space-y-2">
                    <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.requisition_approval') }}</div>
                    <textarea wire:model="approvalNote" rows="2" class="w-full rounded-2xl border-0 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 transition placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900" placeholder="{{ __('candidates::recruitment.labels.approval_note') }}"></textarea>
                    @error('approvalNote') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (! in_array($requisition->approval_status, ['pending', 'approved'], true))
                        <button type="button" wire:click="submitForApproval" class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-950 hover:text-slate-950">
                            {{ __('candidates::recruitment.actions.submit_for_approval') }}
                        </button>
                    @endif
                    @if ($requisition->approval_status !== 'approved')
                        <button type="button" wire:click="approve" class="inline-flex h-12 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(15,23,42,0.8)] transition hover:bg-slate-800">
                            {{ __('candidates::recruitment.actions.approve_requisition') }}
                        </button>
                    @endif
                    @if ($requisition->approval_status !== 'rejected')
                        <button type="button" wire:click="reject" class="inline-flex h-12 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-5 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                            {{ __('candidates::recruitment.actions.reject_requisition') }}
                        </button>
                    @endif
                </div>
            </div>
            @if ($requisition->approval_note)
                <p class="mt-3 rounded-2xl bg-white px-4 py-3 text-sm leading-6 text-slate-600 shadow-sm ring-1 ring-slate-200">{{ $requisition->approval_note }}</p>
            @endif
        </div>

        @if ($requisition->note)
            <div class="mt-6 rounded-[24px] border border-slate-200 bg-white p-5">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.note') }}</div>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $requisition->note }}</p>
            </div>
        @endif
    </section>

    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.labels.openings_count') }}
                </div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                    {{ __('candidates::recruitment.titles.openings') }}
                </h2>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @forelse ($requisition->openings as $opening)
                <a href="{{ route('candidates.openings.show', $opening) }}" class="rounded-[24px] border border-slate-200 bg-slate-50 p-5 transition hover:border-slate-300 hover:bg-white">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $this->recruitmentStatusLabel($opening->status) }}</span>
                        <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ (int) ($opening->getAttributes()['applications_count'] ?? 0) }} {{ __('candidates::recruitment.labels.applications') }}</span>
                    </div>
                    <h3 class="mt-4 text-xl font-semibold tracking-tight text-slate-900">{{ $opening->title }}</h3>
                    <div class="mt-3 text-sm text-slate-500">{{ $opening->structure?->name ?? '—' }} · {{ $opening->position?->name ?? '—' }}</div>
                    <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
                        <span>{{ optional($opening->published_at)->format('d.m.Y') ?? '—' }}</span>
                        <span>{{ optional($opening->closes_at)->format('d.m.Y') ?? '—' }}</span>
                    </div>
                </a>
            @empty
                <div class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                    {{ __('candidates::recruitment.empty.openings') }}
                </div>
            @endforelse
        </div>
    </section>
</div>
