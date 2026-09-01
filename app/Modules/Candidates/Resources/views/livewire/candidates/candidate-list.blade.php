@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $summary = $this->recruitmentSummary;
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('candidates::common.titles.candidates')"
            :subtitle="$num($this->candidateRows->total()).' '.__('candidates::recruitment.labels.candidates_unit')"
        >
            @include('candidates::livewire.candidates.partials.recruitment-context-panel', [
                'panelCounts' => $this->recruitmentPanelCounts(),
            ])

            <x-context-panel.section :title="__('candidates::common.labels.status')">
                <x-context-panel.item wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                    {{ __('candidates::common.labels.all') }}
                </x-context-panel.item>
                @foreach ($this->appealStatusTabs as $_status)
                    <x-context-panel.item
                        wire:key="candidate-panel-status-{{ $_status->id }}"
                        wire:click.prevent="setStatus({{ $_status->id }})"
                        :active="$status === $_status->id"
                    >{{ $_status->name }}</x-context-panel.item>
                @endforeach
                @if ($this->canShowDeletedTab)
                    <x-context-panel.item wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                        {{ __('candidates::common.labels.deleted') }}
                    </x-context-panel.item>
                @endif
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    <div class="lg:hidden">@include('candidates::livewire.candidates.partials.recruitment-nav')</div>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('candidates::common.titles.candidates')"
        :breadcrumb="__('candidates::common.titles.candidates')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($this->candidateRows->total())" :label="__('candidates::recruitment.labels.candidates_unit')" />
            <x-page-header.stat :value="$num($summary['openings'])" :label="__('candidates::recruitment.titles.openings')" tone="blue" />
            <x-page-header.stat :value="$num($summary['active_applications'])" :label="__('candidates::recruitment.statuses.active')" tone="green" />
        </x-slot:stats>

        <x-slot:actions>
            @can('export', App\Models\Candidate::class)
                <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel"
                    wire:loading.attr="disabled" wire:target="exportExcel"
                    title="{{ __('candidates::common.actions.export_excel') }}">
                    <x-icons.excel-icon />
                </x-pill-button>
            @endcan
            @can('create', App\Models\Candidate::class)
                <x-pill-button variant="primary" wire:click="openSideMenu('add-candidate')">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('candidates::common.actions.add_candidate') }}
                </x-pill-button>
            @endcan
        </x-slot:actions>

        {{-- toolbar --}}
        <div class="flex flex-col gap-2.5">
            <div class="flex flex-wrap items-end gap-3">
                @if ($this->filterEnabled('fullname'))
                    <label class="w-full flex-1 sm:max-w-[300px]">
                        <span class="hrm-eyebrow block pb-1">{{ __('candidates::common.labels.fullname') }}</span>
                        <x-livewire-input mode="gray" name="filter.fullname" wire:model="filter.fullname" />
                    </label>
                @endif

                @if ($this->filterEnabled('appeal_date'))
                    <div class="shrink-0">
                        <span class="hrm-eyebrow block pb-1">{{ __('candidates::common.labels.appeal_date') }}</span>
                        <div class="flex items-center gap-2">
                            <input type="date" wire:model="filter.appeal_date.min"
                                class="hrm-num h-[34px] w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] text-ink focus:border-ink focus:bg-white focus:ring-0" />
                            <span class="shrink-0 text-ink-faint">&ndash;</span>
                            <input type="date" wire:model="filter.appeal_date.max"
                                class="hrm-num h-[34px] w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] text-ink focus:border-ink focus:bg-white focus:ring-0" />
                        </div>
                    </div>
                @endif

                @if ($this->filterEnabled('age'))
                    <label class="w-[110px] shrink-0">
                        <span class="hrm-eyebrow block pb-1">{{ __('candidates::common.labels.age') }}</span>
                        <x-livewire-input mode="gray" type="number" name="filter.age" wire:model="filter.age" />
                    </label>
                @endif

                @if ($this->filterEnabled('results') && $this->isMilitaryCandidateMode())
                    <label class="w-[130px] shrink-0">
                        <span class="hrm-eyebrow block pb-1">{{ __('candidates::common.labels.test_results') }}</span>
                        <x-livewire-input mode="gray" type="number" name="filter.results" wire:model="filter.results" />
                    </label>
                @endif

                @if ($this->filterEnabled('document_category'))
                    <div class="min-w-[200px] flex-1">
                        <span class="hrm-eyebrow block pb-1">{{ __('candidates::common.labels.document_category') }}</span>
                        <x-ui.select-dropdown
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="filter.document_category"
                            :model="array_merge([['id' => 'all', 'label' => __('candidates::files.labels.all_categories')]], $this->documentCategoryOptions)"
                        />
                    </div>
                @endif

                <x-pill-button variant="primary" wire:click="searchFilter" class="!h-[34px]">{{ __('candidates::common.labels.search') }}</x-pill-button>
                <x-pill-button wire:click="resetFilter" class="!h-[34px]">{{ __('candidates::common.labels.reset') }}</x-pill-button>
            </div>

            @if ($this->filterEnabled('gender'))
                <div class="flex flex-wrap items-center gap-2">
                    <span class="hrm-eyebrow">{{ __('candidates::common.labels.gender') }}</span>
                    <x-filter.nav wrap class="min-w-0">
                        <x-filter.item
                            wire:click.prevent="$set('filter.gender', null)"
                            :active="blank(data_get($filter, 'gender'))"
                        >{{ __('candidates::common.labels.all') }}</x-filter.item>
                        @foreach (\App\Enums\GenderEnum::genderOptions() as $value => $label)
                            <x-filter.item
                                wire:click.prevent="$set('filter.gender', '{{ $value }}')"
                                :active="(string) data_get($filter, 'gender') === (string) $value"
                            >{{ $label }}</x-filter.item>
                        @endforeach
                    </x-filter.nav>
                </div>
            @endif

            {{-- small-screen fallback for the panel's status list --}}
            <x-filter.nav wrap class="min-w-0 lg:hidden">
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
    </x-page-header>

    @if ($this->documentCategoryStats->isNotEmpty())
        <div class="grid grid-cols-2 gap-2 border-b border-hairline bg-white px-4 py-3 sm:px-5 md:grid-cols-3 xl:grid-cols-6">
            @foreach ($this->documentCategoryStats as $categoryStat)
                <button
                    type="button"
                    wire:key="candidate-doc-category-{{ $categoryStat['id'] }}"
                    wire:click="toggleDocumentCategory('{{ $categoryStat['id'] }}')"
                    @class([
                        'flex flex-col justify-between gap-2 rounded-xl border px-3 py-2.5 text-left transition',
                        'border-ink bg-ink text-white' => $categoryStat['active'],
                        'border-hairline bg-white text-ink hover:border-zinc-300 hover:bg-[#fafafa]' => ! $categoryStat['active'],
                    ])
                >
                    <span @class(['truncate text-[12.5px] font-semibold', 'text-white' => $categoryStat['active']])>{{ $categoryStat['label'] }}</span>
                    <span class="flex items-end justify-between gap-2">
                        <span @class(['hrm-num text-[11px]', 'text-white/70' => $categoryStat['active'], 'text-ink-faint' => ! $categoryStat['active']])>
                            {{ $categoryStat['documents_count'] }} {{ __('candidates::common.labels.document') }}
                        </span>
                        <span @class(['hrm-num text-[11px]', 'text-white/70' => $categoryStat['active'], 'text-ink-faint' => ! $categoryStat['active']])>
                            {{ trans_choice('candidates::common.labels.candidates_count', $categoryStat['candidates_count'], ['count' => $categoryStat['candidates_count']]) }}
                        </span>
                    </span>
                </button>
            @endforeach
        </div>
    @endif

    <x-table.tbl :headers="$this->getTableHeaders()">
        @forelse ($this->candidateRows as $_candidate)
            <tr wire:key="candidate-row-{{ $_candidate->id }}">
                <x-table.td>
                    <span class="hrm-num text-[12px] text-ink-faint">{{ $_candidate->row_no }}</span>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="flex items-start gap-2.5">
                        <x-avatar :name="(string) $_candidate->fullname" size="sm" />
                        <div class="min-w-0 max-w-[320px] leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $_candidate->fullname_max }}</p>

                            @if ($_candidate->latestApplication)
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <x-small-badge mode="blue" dot>{{ $this->recruitmentStageLabel($_candidate->latestApplication->current_stage) }}</x-small-badge>
                                    <x-small-badge mode="secondary">{{ $_candidate->latestApplication->opening?->title ?? __('candidates::recruitment.labels.latest_opening') }}</x-small-badge>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px]">
                                    <a href="{{ route('candidates.applications.show', $_candidate->latestApplication) }}" wire:navigate
                                        class="font-medium text-[#0369a1] transition hover:underline">{{ __('candidates::recruitment.actions.open_latest_application') }}</a>
                                    @if ($_candidate->latestApplication->opening)
                                        <a href="{{ route('candidates.openings.show', $_candidate->latestApplication->opening) }}" wire:navigate
                                            class="font-medium text-[#0369a1] transition hover:underline">{{ __('candidates::recruitment.actions.open_latest_opening') }}</a>
                                    @endif
                                    <a href="{{ route('candidates.applications', ['candidate' => $_candidate->id]) }}" wire:navigate
                                        class="font-medium text-[#0369a1] transition hover:underline">{{ __('candidates::recruitment.actions.open_candidate_pipeline') }}</a>
                                </div>
                            @endif

                            @if (! empty($_candidate->deleted_at))
                                <p class="mt-1 text-[11px] text-ink-faint">
                                    {{ __('candidates::common.labels.deleted_date') }}:
                                    <span class="hrm-num">{{ \Carbon\Carbon::parse($_candidate->deleted_at)->format('d.m.Y H:i') }}</span>
                                    · {{ __('candidates::common.labels.deleted_by') }}: {{ $_candidate->personDidDelete?->name ?? '—' }}
                                </p>
                            @endif
                        </div>
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    <span class="block max-w-[200px] truncate text-[12.5px] text-ink-soft">{{ $_candidate->structure?->name ?? '—' }}</span>
                </x-table.td>

                @if ($this->isMilitaryCandidateMode())
                    <x-table.td>
                        <div class="flex flex-col items-start gap-1">
                            <x-small-badge :mode="$_candidate->knowledge_test_color === 'green' ? 'green' : ($_candidate->knowledge_test_color === 'red' ? 'rose' : 'amber')">
                                {{ __('candidates::common.labels.knowledge') }}: {{ $_candidate->knowledge_test }}
                            </x-small-badge>
                            <x-small-badge :mode="$_candidate->physical_fitness_exam_color === 'green' ? 'green' : ($_candidate->physical_fitness_exam_color === 'red' ? 'rose' : 'amber')">
                                {{ __('candidates::common.labels.physical_fitness') }}: {{ $_candidate->physical_fitness_exam }}
                            </x-small-badge>
                        </div>
                    </x-table.td>
                @endif

                <x-table.td>
                    <div class="leading-tight">
                        <p class="hrm-eyebrow">{{ __('candidates::common.labels.appeal_date') }}</p>
                        <p class="hrm-num text-[12.5px] text-ink-soft">{{ $_candidate->appeal_date ? \Carbon\Carbon::parse($_candidate->appeal_date)->format('d.m.Y') : '—' }}</p>
                    </div>
                </x-table.td>

                <x-table.td>
                    <x-status design="modern" :status-id="$_candidate->status_id" :label="$_candidate->status?->name" />
                </x-table.td>

                <x-table.td>
                    @can('update', $_candidate)
                        <button type="button" wire:click="openSideMenu('candidate-files',{{ $_candidate->id }})"
                            title="{{ __('candidates::common.actions.open_files') }}"
                            class="relative flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                            <x-icons.document-icon color="text-current" hover="text-current" />
                            <span class="hrm-num absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-[#f4f4f5] px-1 py-0.5 text-[10px] font-semibold text-ink-muted">
                                {{ (int) ($_candidate->documents_count ?? 0) }}
                            </span>
                        </button>
                    @endcan
                </x-table.td>

                <x-table.td :isButton="true">
                    <div class="flex items-center justify-end gap-1">
                        @if ($status != 'deleted')
                            @can('update', $_candidate)
                                <button type="button" wire:click="openSideMenu('edit-candidate',{{ $_candidate->id }})"
                                    title="{{ __('candidates::common.actions.edit_candidate') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                    <x-icons.profile-icon color="text-current" hover="text-current" />
                                </button>
                            @endcan
                            @can('delete', $_candidate)
                                <button type="button" wire:click="setDeleteCandidate('{{ $_candidate->id }}')"
                                    title="{{ __('candidates::common.actions.delete') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600">
                                    <x-icons.delete-icon color="text-current" hover="text-current" />
                                </button>
                            @endcan
                        @else
                            @role('Admin')
                                <button type="button" wire:click="restoreData('{{ $_candidate->id }}')"
                                    title="{{ __('candidates::common.actions.restore_candidate') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-teal-50 hover:text-teal-600">
                                    <x-icons.recover color="text-current" hover="text-current" />
                                </button>
                            @endrole
                            @can('delete', $_candidate)
                                <button type="button"
                                    x-on:click="$dispatch('confirm-action', { title: @js(__('candidates::common.actions.force_delete')), message: @js(__('candidates::common.messages.remove_confirm')), confirmText: @js(__('candidates::common.actions.force_delete')), tone: 'rose', run: () => $wire.forceDeleteData('{{ $_candidate->id }}') })"
                                    title="{{ __('candidates::common.actions.force_delete') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600">
                                    <x-icons.force-delete />
                                </button>
                            @endcan
                        @endif
                    </div>
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="count($this->getTableHeaders())" />
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$this->candidateRows" :unit="__('candidates::recruitment.labels.candidates_unit')" />

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
