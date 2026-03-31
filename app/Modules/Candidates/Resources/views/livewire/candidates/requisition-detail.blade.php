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
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.applications_count') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $requisition->openings->count() }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $this->totalApplications }} {{ __('candidates::recruitment.labels.applications') }}</div>
            </div>
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
                        <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ $opening->applications_count }} {{ __('candidates::recruitment.labels.applications') }}</span>
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
