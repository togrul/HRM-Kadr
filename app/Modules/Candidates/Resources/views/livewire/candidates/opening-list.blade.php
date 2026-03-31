<div class="flex flex-col gap-6 px-6 py-4">
    @include('candidates::livewire.candidates.partials.recruitment-nav')

    <section class="grid gap-3 lg:grid-cols-3">
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.openings_count') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->totalOpenings }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.titles.openings') }}</p>
        </div>
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.applications_count') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->activeApplications }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.labels.applications') }}</p>
        </div>
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.timeline') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->totalPublished }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.labels.published_at') }}</p>
        </div>
    </section>

    <section class="rounded-[32px] border border-slate-200 bg-white p-5 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.openings') }}
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('candidates::recruitment.titles.openings') }}
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('candidates::common.labels.search') }}"
                    class="h-11 w-72 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 outline-none transition focus:border-slate-300"
                >
                @can('create', App\Models\Candidate::class)
                    <x-button mode="black" wire:click="openSideMenu('add-opening')">{{ __('candidates::recruitment.actions.add_opening') }}</x-button>
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
                __('candidates::recruitment.labels.requisition_link'),
                __('candidates::recruitment.labels.structure'),
                __('candidates::recruitment.labels.pack_summary'),
                __('candidates::recruitment.labels.applications_count'),
                __('candidates::recruitment.labels.timeline'),
                __('personnel::common.labels.action'),
            ]" :title="__('candidates::recruitment.titles.openings')">
                @forelse ($this->openingRows as $opening)
                    <tr wire:key="opening-row-{{ $opening->id }}">
                        <x-table.td>
                            <div class="space-y-1">
                                <div class="text-sm font-semibold text-slate-900">{{ $opening->title }}</div>
                                @if ($opening->note)
                                    <div class="line-clamp-2 text-xs text-slate-500">{{ $opening->note }}</div>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="font-medium text-slate-900">{{ $opening->requisition?->title ?? '—' }}</div>
                                <div class="text-slate-500">{{ $this->recruitmentStatusLabel($opening->requisition?->status) }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="font-medium text-slate-900">{{ $opening->structure?->name ?? '—' }}</div>
                                <div class="text-slate-500">{{ $opening->position?->name ?? '—' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="flex flex-col gap-2">
                                <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $this->recruitmentPackLabel($opening->profile_pack) }}</span>
                                <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $this->recruitmentStatusLabel($opening->status) }}</span>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="font-semibold text-slate-900">{{ $opening->applications_count }}</div>
                                <div class="text-slate-500">{{ $opening->headcount }} {{ __('candidates::recruitment.labels.headcount_short') }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <div class="space-y-1 text-sm">
                                <div class="text-slate-900">{{ optional($opening->published_at)->format('d.m.Y') ?? '—' }}</div>
                                <div class="text-slate-500">{{ optional($opening->closes_at)->format('d.m.Y') ?? '—' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('candidates.openings.show', $opening) }}" class="inline-flex h-9 items-center rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                    {{ __('candidates::recruitment.actions.open_detail') }}
                                </a>
                                @can('update', App\Models\Candidate::class)
                                    <button type="button" wire:click="openSideMenu('edit-opening', {{ $opening->id }})" class="inline-flex h-9 items-center rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                        {{ __('candidates::recruitment.actions.edit') }}
                                    </button>
                                @endcan
                            </div>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty :rows="7">{{ __('candidates::recruitment.empty.openings') }}</x-table.empty>
                @endforelse
            </x-table.tbl>
        </div>

        <div class="mt-3">
            {{ $this->openingRows->links() }}
        </div>
    </section>

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
