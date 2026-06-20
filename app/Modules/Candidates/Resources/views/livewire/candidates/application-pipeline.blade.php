<div class="flex flex-col gap-6 px-6 py-4">
    @include('candidates::livewire.candidates.partials.recruitment-nav')

    <section class="grid gap-3 lg:grid-cols-3">
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.applications_count') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->totalApplications }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.labels.applications') }}</p>
        </div>
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.openings_count') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->totalOpenings }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.titles.openings') }}</p>
        </div>
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.decision') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->hiredCount }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.labels.hired_label') }}</p>
        </div>
    </section>

    <section class="rounded-[32px] border border-slate-200 bg-white p-5 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.applications') }}
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('candidates::recruitment.titles.pipeline') }}
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('candidates::common.labels.search') }}"
                    class="h-11 w-72 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 outline-none transition focus:border-slate-300"
                >
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            <button type="button" wire:click="setStatus('all')" class="{{ $status === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600' }} rounded-full px-4 py-2 text-sm font-semibold transition">
                {{ __('candidates::common.labels.all') }}
            </button>
            @foreach (['active', 'closed', 'rejected', 'withdrawn'] as $statusOption)
                <button type="button" wire:click="setStatus('{{ $statusOption }}')" class="{{ $status === $statusOption ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600' }} rounded-full px-4 py-2 text-sm font-semibold transition">
                    {{ __('candidates::recruitment.statuses.'.$statusOption) }}
                </button>
            @endforeach
        </div>

        @if ($this->currentOpening || $this->currentCandidate || $this->recruitmentPackSelectorVisible())
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($this->currentOpening)
                    <button type="button" wire:click="setOpening('all')" class="rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 transition">
                        {{ $this->currentOpening->title }}
                    </button>
                @endif
                @if ($this->currentCandidate)
                    <button type="button" wire:click="setCandidate('all')" class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition">
                        {{ $this->currentCandidate->fullname }}
                    </button>
                @endif
                @if ($this->recruitmentPackSelectorVisible())
                    <button type="button" wire:click="setPack('all')" class="{{ $pack === 'all' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500' }} rounded-full border px-4 py-2 text-sm font-semibold transition">
                        {{ __('candidates::common.labels.all') }}
                    </button>
                    @foreach ($this->recruitmentAvailablePacks() as $packOption)
                        <button type="button" wire:click="setPack('{{ $packOption }}')" class="{{ $pack === $packOption ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500' }} rounded-full border px-4 py-2 text-sm font-semibold transition">
                            {{ __('candidates::recruitment.packs.'.$packOption) }}
                        </button>
                    @endforeach
                @endif
            </div>
        @endif

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->stageSummary as $stageSummary)
                <button
                    type="button"
                    wire:click="setStage('{{ $stageSummary['key'] }}')"
                    class="{{ $stage === $stageSummary['key'] ? 'border-slate-900 bg-slate-900 text-white' : ($stageSummary['terminal'] ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-900') }} rounded-[24px] border p-4 text-left transition"
                >
                    <div class="text-[11px] font-semibold uppercase tracking-tight {{ $stage === $stageSummary['key'] ? 'text-slate-300' : ($stageSummary['terminal'] ? 'text-rose-400' : 'text-slate-400') }}">
                        {{ __('candidates::recruitment.labels.pipeline_stage') }}
                    </div>
                    <div class="mt-3 text-lg font-semibold tracking-tight">{{ $stageSummary['label'] }}</div>
                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $stageSummary['count'] }}</div>
                </button>
            @endforeach
        </div>

        <div class="mt-3">
            <button type="button" wire:click="setStage('all')" class="{{ $stage === 'all' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500' }} rounded-full border px-4 py-2 text-sm font-semibold transition">
                {{ __('candidates::recruitment.actions.show_all_stages') }}
            </button>
        </div>

        <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-200">
            <x-table.tbl :headers="[
                __('candidates::recruitment.labels.candidate'),
                __('candidates::recruitment.labels.opening'),
                __('candidates::recruitment.labels.current_stage'),
                __('candidates::recruitment.labels.source'),
                __('candidates::recruitment.labels.assigned_recruiter'),
                __('candidates::recruitment.labels.timeline'),
                __('personnel::common.labels.action'),
            ]" :title="__('candidates::recruitment.titles.applications')">
                @forelse ($this->applicationRows as $application)
                    <tr wire:key="candidate-application-row-{{ $application->id }}">
                        <x-table.td>
                            <div class="space-y-1">
                                <div class="text-sm font-semibold text-slate-900">{{ $application->candidate?->fullname ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $application->candidate?->phone ?? '—' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="font-medium text-slate-900">{{ $application->opening?->title ?? '—' }}</div>
                                <div class="text-slate-500">{{ $application->opening?->structure?->name ?? '—' }} / {{ $application->opening?->position?->name ?? '—' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="flex flex-col gap-2">
                                <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $this->recruitmentStageLabel($application->current_stage) }}</span>
                                <span class="inline-flex w-fit rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ $this->recruitmentStatusLabel($application->status) }}</span>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="text-sm text-slate-700">{{ $application->source?->name ?? '—' }}</div>
                        </x-table.td>
                        <x-table.td>
                            <div class="text-sm text-slate-700">{{ $application->assignedRecruiter?->name ?? '—' }}</div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="text-slate-900">{{ optional($application->applied_at)->format('d.m.Y H:i') ?? '—' }}</div>
                                <div class="text-slate-500">{{ optional($application->moved_at)->format('d.m.Y H:i') ?? '—' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            <a href="{{ route('candidates.applications.show', $application) }}" class="inline-flex h-9 items-center rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                {{ __('candidates::recruitment.actions.open_application') }}
                            </a>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty :rows="7">{{ __('candidates::recruitment.empty.pipeline') }}</x-table.empty>
                @endforelse
            </x-table.tbl>
        </div>

        <div class="mt-3">
            {{ $this->applicationRows->links() }}
        </div>
    </section>
</div>
