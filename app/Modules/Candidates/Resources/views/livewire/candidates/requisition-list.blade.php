<div class="flex flex-col gap-6 px-6 py-4">
    @include('candidates::livewire.candidates.partials.recruitment-nav')

    <section class="grid gap-3 lg:grid-cols-3">
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.status') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->draftCount }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.statuses.draft') }}</p>
        </div>
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.openings_count') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->openCount }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.statuses.open') }}</p>
        </div>
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.headcount') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->totalHeadcount }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.labels.headcount_short') }}</p>
        </div>
    </section>

    <section class="rounded-[32px] border border-slate-200 bg-white p-5 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.requisitions') }}
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('candidates::recruitment.titles.requisitions') }}
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('candidates::common.labels.search') }}"
                        class="h-11 w-72 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 outline-none transition focus:border-slate-300"
                    >
                </div>
                @can('create', App\Models\Candidate::class)
                    <x-button mode="black" wire:click="openSideMenu('add-requisition')">{{ __('candidates::recruitment.actions.add_requisition') }}</x-button>
                @endcan
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            <button type="button" wire:click="setStatus('all')" class="{{ $status === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600' }} rounded-full px-4 py-2 text-sm font-semibold transition">
                {{ __('candidates::common.labels.all') }}
            </button>
            @foreach (['draft', 'open', 'closed', 'cancelled'] as $statusOption)
                <button type="button" wire:click="setStatus('{{ $statusOption }}')" class="{{ $status === $statusOption ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600' }} rounded-full px-4 py-2 text-sm font-semibold transition">
                    {{ __('candidates::recruitment.statuses.'.$statusOption) }}
                </button>
            @endforeach
        </div>

        @if ($this->recruitmentPackSelectorVisible())
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" wire:click="setPack('all')" class="{{ $pack === 'all' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500' }} rounded-full border px-4 py-2 text-sm font-semibold transition">
                    {{ __('candidates::common.labels.all') }}
                </button>
                @foreach ($this->recruitmentAvailablePacks() as $packOption)
                    <button type="button" wire:click="setPack('{{ $packOption }}')" class="{{ $pack === $packOption ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500' }} rounded-full border px-4 py-2 text-sm font-semibold transition">
                        {{ __('candidates::recruitment.packs.'.$packOption) }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-200">
            <x-table.tbl :headers="[
                __('candidates::recruitment.labels.title'),
                __('candidates::recruitment.labels.structure'),
                __('candidates::recruitment.labels.pack_summary'),
                __('candidates::recruitment.labels.owner_summary'),
                __('candidates::recruitment.labels.timeline'),
                __('candidates::recruitment.labels.openings_count'),
                __('personnel::common.labels.action'),
            ]" :title="__('candidates::recruitment.titles.requisitions')">
                @forelse ($this->requisitionRows as $requisition)
                    <tr wire:key="requisition-row-{{ $requisition->id }}">
                        <x-table.td>
                            <div class="space-y-1">
                                <div class="text-sm font-semibold text-slate-900">{{ $requisition->title }}</div>
                                @if ($requisition->hiring_reason)
                                    <div class="text-xs text-slate-500">{{ $requisition->hiring_reason }}</div>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="font-medium text-slate-900">{{ $requisition->structure?->name ?? '—' }}</div>
                                <div class="text-slate-500">{{ $requisition->position?->name ?? '—' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="flex flex-col gap-2">
                                <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $this->recruitmentPackLabel($requisition->profile_pack) }}</span>
                                <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $this->recruitmentStatusLabel($requisition->status) }}</span>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="font-medium text-slate-900">{{ $requisition->owner?->name ?? '—' }}</div>
                                <div class="text-slate-500">{{ $requisition->requester?->name ?? '—' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="text-slate-900">{{ optional($requisition->opens_at)->format('d.m.Y') ?? '—' }}</div>
                                <div class="text-slate-500">{{ optional($requisition->closes_at)->format('d.m.Y') ?? '—' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="font-semibold text-slate-900">{{ $requisition->openings_count }}</div>
                                <div class="text-slate-500">{{ $requisition->headcount }} {{ __('candidates::recruitment.labels.headcount_short') }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('candidates.requisitions.show', $requisition) }}" class="inline-flex h-9 items-center rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                    {{ __('candidates::recruitment.actions.open_detail') }}
                                </a>
                                @can('update', App\Models\Candidate::class)
                                    <button type="button" wire:click="openSideMenu('edit-requisition', {{ $requisition->id }})" class="inline-flex h-9 items-center rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                        {{ __('candidates::recruitment.actions.edit') }}
                                    </button>
                                @endcan
                            </div>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty :rows="7">{{ __('candidates::recruitment.empty.requisitions') }}</x-table.empty>
                @endforelse
            </x-table.tbl>
        </div>

        <div class="mt-3">
            {{ $this->requisitionRows->links() }}
        </div>
    </section>

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
