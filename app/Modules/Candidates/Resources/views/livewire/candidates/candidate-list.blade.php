<div
    class="flex flex-col"
    x-data
    x-init="
        const root = $el;
        const applyPaginatorTheme = (isUpdate = false) => {
            const paginator = root.querySelector('span[aria-current=page]>span');
            if (!paginator) return;
            paginator.classList.remove('bg-blue-50', 'text-blue-600', 'bg-green-100', 'text-green-600');
            paginator.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
        };

        applyPaginatorTheme(false);
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', ({ component, succeed }) => {
                if (component.id !== @js($this->getId())) return;
                succeed(() => queueMicrotask(() => applyPaginatorTheme(true)));
            });
        }
    "
>
    <div class="px-6 pt-4">
        @include('candidates::livewire.candidates.partials.recruitment-nav')
    </div>

    <div class="grid grid-cols-1 gap-3 px-6 py-2 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_18px_40px_-34px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.requisitions') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->recruitmentSummary['requisitions'] }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.labels.recruitment_owner') }}</p>
        </div>
        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_18px_40px_-34px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.openings') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->recruitmentSummary['openings'] }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.labels.openings_count') }}</p>
        </div>
        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_18px_40px_-34px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.pipeline') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->recruitmentSummary['applications'] }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.labels.applications') }}</p>
        </div>
        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_18px_40px_-34px_rgba(15,23,42,0.35)]">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.current_stage') }}</div>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $this->recruitmentSummary['active_applications'] }}</div>
            <p class="mt-2 text-sm text-slate-500">{{ __('candidates::recruitment.statuses.active') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
        @if ($this->filterEnabled('fullname'))
            <div class="flex flex-col">
                <x-label for="filter.fullname">{{ __('candidates::common.labels.fullname') }}</x-label>
                <x-livewire-input mode="gray" name="filter.fullname" wire:model="filter.fullname"></x-livewire-input>
            </div>
        @endif
        @if ($this->filterEnabled('gender'))
            <div class="flex flex-col w-full space-y-1">
                <x-label for="filter.gender">{{ __('candidates::common.labels.gender') }}</x-label>
                <div class="flex space-x-2">
                    @foreach (\App\Enums\GenderEnum::genderOptions() as $value => $label)
                        <label class="inline-flex items-center w-full px-2 py-2 bg-gray-100 rounded shadow-sm">
                            <input type="radio" class="form-radio" name="filter.gender" wire:model="filter.gender"
                                value="{{ $value }}">
                            <span class="ml-2 text-sm font-normal">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="flex items-center space-x-2">
            @if ($this->filterEnabled('results') && $this->isMilitaryCandidateMode())
                <div class="flex flex-col">
                    <x-label for="filter.results">{{ __('candidates::common.labels.test_results') }}</x-label>
                    <x-livewire-input mode="gray" type="number" name="filter.results"
                        wire:model="filter.results"></x-livewire-input>
                </div>
            @endif
            @if ($this->filterEnabled('age'))
                <div class="flex flex-col">
                    <x-label for="filter.age">{{ __('candidates::common.labels.age') }}</x-label>
                    <x-livewire-input mode="gray" type="number" name="filter.age"
                        wire:model="filter.age"></x-livewire-input>
                </div>
            @endif
        </div>

        @if ($this->filterEnabled('appeal_date'))
            <div class="flex flex-col">
                <x-label for="filter.appeal_date">{{ __('candidates::common.labels.appeal_date') }}</x-label>
                <div class="flex items-center space-x-1">
                    <x-pikaday-input mode="gray" name="filter.appeal_date.min" format="Y-MM-DD"
                        wire:model="filter.appeal_date.min">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('filter.appeal_date.min', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    <span>-</span>
                    <x-pikaday-input mode="gray" name="filter.appeal_date.max" format="Y-MM-DD"
                        wire:model="filter.appeal_date.max">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('filter.appeal_date.max', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
            </div>
        @endif
        @if ($this->filterEnabled('document_category'))
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    :label="__('candidates::common.labels.document_category')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="filter.document_category"
                    :model="array_merge([['id' => 'all', 'label' => __('candidates::files.labels.all_categories')]], $this->documentCategoryOptions)"
                />
            </div>
        @endif
        <div class="flex items-end space-x-2">
            <x-button mode="primary" wire:click="searchFilter">{{ __('candidates::common.labels.search') }}</x-button>
            <x-button mode="black" wire:click="resetFilter">{{ __('candidates::common.labels.reset') }}</x-button>
        </div>
    </div>

    <div class="px-6">
        <div class="flex flex-col items-center justify-between px-2 py-2 bg-white sm:flex-row filter rounded-xl">
            <x-filter.nav>
                <x-filter.item wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                    {{ __('candidates::common.labels.all') }}
                </x-filter.item>
                @foreach ($this->appealStatusTabs as $_status)
                    <x-filter.item wire:click.prevent="setStatus({{ $_status->id }})" :active="$status === $_status->id">
                        {{ $_status->name }}
                    </x-filter.item>
                @endforeach
                @if ($this->canShowDeletedTab)
                    <x-filter.item wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                        {{ __('candidates::common.labels.deleted') }}
                    </x-filter.item>
                @endif
            </x-filter.nav>
        </div>
    </div>

    <div class="flex flex-col px-6 py-4 space-y-4">
        @if ($this->documentCategoryStats->isNotEmpty())
            <section class="grid grid-cols-1 gap-2 md:grid-cols-3 xl:grid-cols-6">
                @foreach ($this->documentCategoryStats as $categoryStat)
                    <button
                        type="button"
                        wire:click="toggleDocumentCategory('{{ $categoryStat['id'] }}')"
                        class="{{ $categoryStat['active'] ? 'border-slate-800 bg-[linear-gradient(180deg,#111827_0%,#0f172a_100%)] text-white shadow-[0_18px_34px_-24px_rgba(15,23,42,0.58)] ring-1 ring-slate-700/30' : 'border-slate-200 bg-white text-slate-900 hover:border-slate-300 hover:bg-slate-50 hover:shadow-[0_16px_30px_-24px_rgba(15,23,42,0.25)]' }} flex rounded-[24px] border px-4 py-3 text-left transition"
                    >
                        <div class="flex w-full flex-col justify-between gap-1">
                            <div class="space-y-2">
                                <div class="{{ $categoryStat['active'] ? 'text-slate-300' : 'text-slate-400' }} text-[11px] font-semibold uppercase tracking-tight">
                                    0{{ $loop->iteration }}
                                </div>
                                <div class="text-[1rem] font-semibold leading-6">{{ $categoryStat['label'] }}</div>
                            </div>

                            <div class="flex items-end justify-between gap-3">
                                <div class="{{ $categoryStat['active'] ? 'text-slate-300' : 'text-slate-500' }} text-sm font-medium">
                                    {{ $categoryStat['documents_count'] }} {{ __('candidates::common.labels.document') }}
                                </div>
                                <div class="{{ $categoryStat['active'] ? 'text-slate-300' : 'text-slate-500' }} text-xs font-medium text-right">
                                    {{ trans_choice('candidates::common.labels.candidates_count', $categoryStat['candidates_count'], ['count' => $categoryStat['candidates_count']]) }}
                                </div>
                            </div>
                        </div>
                    </button>
                @endforeach
            </section>
        @endif

        <div class="flex items-center justify-between">
            <div class="flex flex-col gap-4">
                <div class="flex space-x-4">
                    @can('create', App\Models\Candidate::class)
                        <button wire:click="openSideMenu('add-candidate')"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-blue-50"
                            type="button">
                            <x-icons.add-file></x-icons.add-file>
                        </button>
                    @endcan
                    @can('export', App\Models\Candidate::class)
                        <button wire:click.prevent="exportExcel"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-green-50"
                            type="button">
                            <x-icons.excel-icon />
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">

                    <x-table.tbl :headers="$this->getTableHeaders()" title="{{ __('candidates::common.titles.candidates') }}">
                        @forelse ($this->candidateRows as $_candidate)
                            <tr wire:key="candidate-row-{{ $_candidate->id }}">
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $_candidate->row_no }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_candidate->fullname_max }}
                                        </span>
                                        @if ($_candidate->latestApplication)
                                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                                <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                                                    {{ $this->recruitmentStageLabel($_candidate->latestApplication->current_stage) }}
                                                </span>
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                    {{ $_candidate->latestApplication->opening?->title ?? __('candidates::recruitment.labels.latest_opening') }}
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-2 pt-1">
                                                <a href="{{ route('candidates.applications.show', $_candidate->latestApplication) }}" class="inline-flex h-8 items-center rounded-xl border border-slate-200 px-3 text-[11px] font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                    {{ __('candidates::recruitment.actions.open_latest_application') }}
                                                </a>
                                                @if ($_candidate->latestApplication->opening)
                                                    <a href="{{ route('candidates.openings.show', $_candidate->latestApplication->opening) }}" class="inline-flex h-8 items-center rounded-xl border border-slate-200 px-3 text-[11px] font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                        {{ __('candidates::recruitment.actions.open_latest_opening') }}
                                                    </a>
                                                @endif
                                                <a href="{{ route('candidates.applications', ['candidate' => $_candidate->id]) }}" class="inline-flex h-8 items-center rounded-xl border border-slate-200 px-3 text-[11px] font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                    {{ __('candidates::recruitment.actions.open_candidate_pipeline') }}
                                                </a>
                                            </div>
                                        @endif
                                        @if (!empty($_candidate->deleted_at))
                                            <div class="flex flex-col text-xs font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('candidates::common.labels.deleted_date') }}:</span>
                                                    <span
                                                        class="text-black">{{ \Carbon\Carbon::parse($_candidate->deleted_at)->format('d-m-Y H:i') }}</span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('candidates::common.labels.deleted_by') }}:</span>
                                                    <span
                                                        class="text-black">{{ $_candidate->personDidDelete->name }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                </x-table.td>

                                <x-table.td>
                                    <span
                                        class="px-2 py-2 text-sm font-medium text-gray-900 rounded-lg bg-slate-100">{{ $_candidate->structure->name }}</span>
                                </x-table.td>

                                @if ($this->isMilitaryCandidateMode())
                                    <x-table.td>
                                        <div class="flex flex-col space-y-1">
                                            <div class="flex items-center space-x-1">
                                                <span
                                                    class="text-sm font-medium text-gray-500">{{ __('candidates::common.labels.knowledge') }}:</span>
                                                <span
                                                    class="text-sm font-medium px-2 py-1 rounded-lg bg-{{ $_candidate->knowledge_test_color }}-100 text-{{ $_candidate->knowledge_test_color }}-500">{{ $_candidate->knowledge_test }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <span
                                                    class="text-sm font-medium text-gray-500">{{ __('candidates::common.labels.physical_fitness') }}:</span>
                                                <span
                                                    class="text-sm font-medium px-2 py-1 rounded-lg bg-{{ $_candidate->physical_fitness_exam_color }}-100 text-{{ $_candidate->physical_fitness_exam_color }}-500">{{ $_candidate->physical_fitness_exam }}</span>
                                            </div>
                                        </div>
                                    </x-table.td>
                                @endif

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <x-table.cell-vertical title="{{ __('candidates::common.labels.appeal_date') }}">
                                            {{ \Carbon\Carbon::parse($_candidate->appeal_date)->format('d.m.Y') }}
                                        </x-table.cell-vertical>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <x-status  design="modern" :status-id="$_candidate->status_id" :label="$_candidate->status->name"></x-status>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @can('update', $_candidate)
                                        <button
                                            wire:click="openSideMenu('candidate-files',{{ $_candidate->id }})"
                                            class="relative flex items-center justify-center w-10 h-10 text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg hover:bg-gray-200 hover:text-gray-700"
                                            title="{{ __('candidates::common.labels.files') }}"
                                        >
                                            <x-icons.document-icon></x-icons.document-icon>
                                            <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-600">
                                                {{ (int) ($_candidate->documents_count ?? 0) }}
                                            </span>
                                        </button>
                                    @endcan
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status != 'deleted')
                                        @can('update', $_candidate)
                                            <a href="#"
                                                wire:click="openSideMenu('edit-candidate',{{ $_candidate->id }})"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg hover:bg-gray-200 hover:text-gray-700">
                                                <x-icons.profile-icon></x-icons.profile-icon>
                                            </a>
                                        @endcan
                                    @else
                                        @role('Admin')
                                            <button wire:click="restoreData('{{ $_candidate->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-teal-50 hover:text-gray-700">
                                                <x-icons.recover color="text-teal-500" hover="text-teal-600"></x-icons.recover>
                                            </button>
                                        @endrole
                                    @endif
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status != 'deleted')
                                        @can('delete', $_candidate)
                                            <button wire:click="setDeleteCandidate('{{ $_candidate->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-100 hover:text-gray-700">
                                                <x-icons.delete-icon></x-icons.delete-icon>
                                            </button>
                                        @endcan
                                    @else
                                        @can('delete', $_candidate)
                                            <button wire:confirm="{{ __('candidates::common.messages.remove_confirm') }}"
                                                wire:click="forceDeleteData('{{ $_candidate->id }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700">
                                                <x-icons.force-delete></x-icons.force-delete>
                                            </button>
                                        @endcan
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty :rows="count($this->getTableHeaders())"></x-table.empty>
                        @endforelse
                    </x-table.tbl>

                </div>
            </div>
        </div>

        <div class="mt-2">
            {{ $this->candidateRows->links() }}
        </div>
    </div>

    <x-side-modal>
        @can('create', App\Models\Candidate::class)
            @if ($showSideMenu == 'add-candidate')
                <livewire:candidates.add-candidate wire:key="candidate-add-modal" lazy />
            @endif
        @endcan

        @if ($showSideMenu === 'edit-candidate')
            <livewire:candidates.edit-candidate :candidateModel="$modelName" :key="'candidate-edit-modal-' . ($modelName ?? 'none')" />
        @endif

        @if ($showSideMenu === 'candidate-files')
            <livewire:candidates.candidate-files :candidateModel="$modelName" :key="'candidate-files-modal-' . ($modelName ?? 'none')" />
        @endif
    </x-side-modal>

    @can('delete', App\Models\Candidate::class)
        <div>
            <livewire:candidates.delete-candidate wire:key="candidate-delete-modal" />
        </div>
    @endcan

    <x-datepicker :auto=false></x-datepicker>
</div>
