@php
    $labelClass = 'hrm-eyebrow block pb-1';
    $tabs = $this->tabs();
    $statusTone = fn (string $status): string => match ($status) {
        'open' => 'sky',
        'investigating' => 'amber',
        'decided', 'final' => 'violet',
        'appealed', 'appeal_review' => 'rose',
        default => 'secondary',
    };
    $minGroup = max(1, (int) config('employee_relations.statistics.min_group_size', 3));
    $num = fn ($value): string => $value === null ? '<'.$minGroup : number_format((int) $value, 0, ',', ' ');
@endphp

<div class="flex flex-col">
    <x-page-header
        :title="__('employee_relations::cases.title')"
        :breadcrumb="__('employee_relations::cases.tabs.'.$activeTab)"
        :breadcrumb-root="__('employee_relations::cases.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            @if ($activeTab === 'cases' && $canCreate)
                <x-pill-button variant="primary" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('employee_relations::cases.actions.new_case') }}
                </x-pill-button>
            @endif
        </x-slot:actions>

        <div class="flex flex-col gap-3">
            <p class="max-w-3xl text-[13px] leading-6 text-ink-muted">{{ __('employee_relations::cases.description') }}</p>

            @if (count($tabs) > 1)
                <x-filter.nav wrap class="min-w-0">
                    @foreach ($tabs as $tab)
                        <x-filter.item
                            wire:key="er-tab-{{ $tab }}"
                            wire:click.prevent="setTab('{{ $tab }}')"
                            :active="$activeTab === $tab"
                        >{{ __('employee_relations::cases.tabs.'.$tab) }}</x-filter.item>
                    @endforeach
                </x-filter.nav>
            @endif
        </div>
    </x-page-header>

    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        {{-- ===================== cases ===================== --}}
        @if ($activeTab === 'cases')
            @if ($showCreate)
                <section class="overflow-hidden rounded-xl border border-hairline bg-white" wire:key="er-create-form">
                    <div class="flex items-center justify-between border-b border-hairline-subtle px-4 py-3">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.new_case') }}</h2>
                        <x-pill-button variant="ghost" wire:click="closeCreate">{{ __('employee_relations::cases.actions.cancel') }}</x-pill-button>
                    </div>

                    <div class="grid gap-3 p-4 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.type')" :error="$errors->first('form.case_type_id')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.select wire:model="form.case_type_id">
                                @foreach ($this->typeOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('employee_relations::cases.fields.title')" :error="$errors->first('form.title')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model="form.title" type="text" />
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('employee_relations::cases.fields.description')" :error="$errors->first('form.description')" containerClass="!space-y-0 sm:col-span-2" :labelClass="$labelClass">
                            <x-ui.textarea wire:model="form.description" :rows="4" />
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('employee_relations::cases.fields.occurred_at')" :error="$errors->first('form.occurred_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model="form.occurred_at" type="date" class="hrm-num" />
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('employee_relations::cases.fields.discovered_at')" :hint="__('employee_relations::cases.hints.discovered_at')" :error="$errors->first('form.discovered_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model="form.discovered_at" type="date" class="hrm-num" />
                        </x-ui.input-shell>

                        @foreach (['subject_personnel_id' => 'subjectSearch', 'complainant_personnel_id' => 'complainantSearch'] as $field => $searchProperty)
                            <x-ui.input-shell :label="__('employee_relations::cases.fields.'.$field)" :error="$errors->first('form.'.$field)" containerClass="!space-y-0" :labelClass="$labelClass" wire:key="er-pick-{{ $field }}">
                                @if (filled($form[$field] ?? null))
                                    <div class="flex items-center justify-between gap-2 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2 text-[13px] text-ink">
                                        <span class="truncate">{{ $form[$field.'_label'] ?? '' }}</span>
                                        <button type="button" class="text-[12px] text-ink-muted hover:text-ink" wire:click="clearPersonnel('{{ $field }}')">{{ __('employee_relations::cases.actions.clear') }}</button>
                                    </div>
                                @else
                                    <x-ui.input icon="search" wire:model.live.debounce.300ms="{{ $searchProperty }}" :placeholder="__('employee_relations::cases.placeholders.personnel_search')" />
                                    @php $results = $this->personnelResults($this->{$searchProperty}); @endphp
                                    @if ($results !== [])
                                        <div class="mt-1 overflow-hidden rounded-lg border border-hairline bg-white">
                                            @foreach ($results as $result)
                                                <button type="button" wire:key="er-pick-{{ $field }}-{{ $result['id'] }}" wire:click="pickPersonnel('{{ $field }}', {{ $result['id'] }})" class="flex w-full flex-col px-3 py-1.5 text-left hover:bg-[#fafafa]">
                                                    <span class="text-[12.5px] font-medium text-ink">{{ $result['name'] }}</span>
                                                    <span class="text-[11px] text-ink-faint">{{ $result['meta'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </x-ui.input-shell>
                        @endforeach

                        <div class="flex flex-col gap-2 sm:col-span-2">
                            <x-checkbox name="form.is_anonymous" model="form.is_anonymous">{{ __('employee_relations::cases.fields.is_anonymous') }}</x-checkbox>
                            <x-checkbox name="form.discovered_by_audit" model="form.discovered_by_audit">{{ __('employee_relations::cases.fields.discovered_by_audit') }}</x-checkbox>
                            <p class="text-[11.5px] leading-5 text-ink-faint">{{ __('employee_relations::cases.hints.anonymous') }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-hairline-subtle px-4 py-3">
                        <x-pill-button variant="primary" wire:click="saveCase" wire:loading.attr="disabled" wire:target="saveCase">{{ __('employee_relations::cases.actions.open_case') }}</x-pill-button>
                    </div>
                </section>
            @endif

            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex flex-col gap-2.5 border-b border-hairline-subtle px-4 py-3 lg:flex-row lg:items-center">
                    <div class="w-full lg:max-w-[320px]">
                        <x-ui.input icon="search" wire:model.live.debounce.300ms="search" :placeholder="__('employee_relations::cases.placeholders.search')" />
                    </div>
                    <div class="w-full lg:w-52">
                        <x-ui.select wire:model.live="status">
                            <option value="">{{ __('employee_relations::cases.filters.all_statuses') }}</option>
                            @foreach ($this->statusOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="w-full lg:w-52">
                        <x-ui.select wire:model.live="typeId">
                            <option value="">{{ __('employee_relations::cases.filters.all_types') }}</option>
                            @foreach ($this->typeOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <x-checkbox name="overdueOnly" model="overdueOnly">{{ __('employee_relations::cases.filters.overdue_only') }}</x-checkbox>
                    <x-checkbox name="cancellationPending" model="cancellationPending">{{ __('employee_relations::cases.filters.cancellation_pending') }}</x-checkbox>
                    <div class="lg:ml-auto">
                        <x-filter.reset :active="$search !== '' || filled($status) || filled($typeId) || $overdueOnly || $cancellationPending" action="resetFilters" />
                    </div>
                </div>

                @php $cases = $this->cases; @endphp
                <x-table.tbl :headers="[
                    __('employee_relations::cases.columns.case'),
                    __('employee_relations::cases.columns.type'),
                    __('employee_relations::cases.columns.subject'),
                    __('employee_relations::cases.columns.status'),
                    __('employee_relations::cases.columns.due'),
                ]">
                    @forelse ($cases as $row)
                        <tr wire:key="er-case-{{ $row->id }}">
                            <x-table.td standart-width>
                                <a href="{{ route('employee-relations.cases.show', ['case' => $row->id]) }}" wire:navigate class="block max-w-[280px]">
                                    <p class="hrm-num text-[12px] text-ink-faint">{{ $row->label() }}</p>
                                    <p class="truncate text-[13px] font-medium text-ink hover:underline">{{ $row->title }}</p>
                                </a>
                            </x-table.td>
                            <x-table.td>{{ $row->type?->name }}</x-table.td>
                            <x-table.td standart-width>
                                <p class="max-w-[220px] truncate text-[13px] text-ink-soft">{{ $row->subject?->fullname ?? ($row->subject_description ?: '—') }}</p>
                                <p class="max-w-[220px] truncate text-[11px] text-ink-faint">{{ $row->structure?->name }}</p>
                            </x-table.td>
                            <x-table.td>
                                <x-small-badge :mode="$statusTone($row->status->value)" dot>{{ $row->status->label() }}</x-small-badge>
                                @if ($row->hasPendingOrderCancellation())
                                    <x-small-badge mode="rose" class="mt-1">{{ __('employee_relations::cases.order_cancellation.badge') }}</x-small-badge>
                                @endif
                            </x-table.td>
                            <x-table.td>
                                @if ($row->isOverdue())
                                    <x-small-badge mode="rose">{{ __('employee_relations::cases.labels.overdue_since', ['date' => $row->stage_due_at?->format('d.m.Y')]) }}</x-small-badge>
                                @else
                                    <span class="hrm-num text-[12.5px] text-ink-soft">{{ $row->status->hasSla() ? $row->stage_due_at?->format('d.m.Y') : '—' }}</span>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty :rows="5" :filtered="$search !== '' || filled($status) || filled($typeId) || $overdueOnly || $cancellationPending" />
                    @endforelse
                </x-table.tbl>

                @if ($cases->hasPages())
                    <x-pagination :paginator="$cases" />
                @endif
            </section>
        @endif

        {{-- ===================== statistics ===================== --}}
        @if ($activeTab === 'statistics')
            @php $stats = $this->statistics; @endphp
            <section class="flex flex-col gap-2.5 rounded-xl border border-hairline bg-white px-4 py-3 lg:flex-row lg:items-end">
                <x-ui.input-shell :label="__('employee_relations::cases.fields.structure')" containerClass="!space-y-0 w-full lg:w-72" :labelClass="$labelClass">
                    <x-ui.select wire:model.live="statsStructure">
                        <option value="">{{ __('employee_relations::cases.filters.all_structures') }}</option>
                        @foreach ($this->structureOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('employee_relations::cases.fields.from')" containerClass="!space-y-0" :labelClass="$labelClass">
                    <x-ui.input wire:model.live="statsFrom" type="date" class="hrm-num" />
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('employee_relations::cases.fields.to')" containerClass="!space-y-0" :labelClass="$labelClass">
                    <x-ui.input wire:model.live="statsTo" type="date" class="hrm-num" />
                </x-ui.input-shell>
                <p class="text-[11.5px] leading-5 text-ink-faint lg:ml-auto lg:max-w-sm">{{ __('employee_relations::cases.statistics.privacy_note', ['size' => $stats['min_group_size']]) }}</p>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                <x-ui.metric-tile :label="__('employee_relations::cases.statistics.total')" :value="$num($stats['total'])" />
                <x-ui.metric-tile :label="__('employee_relations::cases.statistics.open')" :value="$num($stats['open'])" tone="blue" />
                <x-ui.metric-tile :label="__('employee_relations::cases.statistics.overdue')" :value="$num($stats['overdue'])" tone="rose" />
                <x-ui.metric-tile :label="__('employee_relations::cases.statistics.sla_breaches')" :value="$num($stats['sla_breaches'])" tone="amber" />
                <x-ui.metric-tile :label="__('employee_relations::cases.statistics.repeat_subjects')" :value="$num($stats['repeat_subjects'])" :hint="__('employee_relations::cases.statistics.repeat_hint', ['count' => $stats['repeat_threshold']])" tone="violet" />
                <x-ui.metric-tile :label="__('employee_relations::cases.statistics.avg_days')" :value="$stats['avg_investigation_days'] === null ? '—' : number_format((float) $stats['avg_investigation_days'], 1, ',', ' ')" :hint="__('employee_relations::cases.statistics.avg_days_hint')" />
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                @foreach (['by_structure', 'by_type', 'by_status'] as $group)
                    <div class="overflow-hidden rounded-xl border border-hairline bg-white" wire:key="er-stats-{{ $group }}">
                        <div class="border-b border-hairline-subtle px-4 py-3">
                            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.statistics.'.$group) }}</h2>
                        </div>
                        <div class="divide-y divide-hairline-subtle">
                            @forelse ($stats[$group] as $row)
                                <div class="flex items-baseline justify-between gap-3 px-4 py-2" wire:key="er-stats-{{ $group }}-{{ $loop->index }}">
                                    <span class="min-w-0 truncate text-[12.5px] text-ink-soft">{{ $row['label'] }}</span>
                                    <span class="hrm-num shrink-0 text-[13px] font-semibold text-ink">{{ $num($row['value']) }}</span>
                                </div>
                            @empty
                                <p class="px-4 py-6 text-center text-[12.5px] text-ink-faint">{{ __('employee_relations::cases.labels.no_data') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- ===================== types ===================== --}}
        @if ($activeTab === 'types' && $canManage)
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ $editingTypeId ? __('employee_relations::cases.sections.edit_type') : __('employee_relations::cases.sections.new_type') }}</h2>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-3">
                    <x-ui.input-shell :label="__('employee_relations::cases.fields.type_name')" :error="$errors->first('typeForm.name')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model="typeForm.name" type="text" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('employee_relations::cases.fields.investigation_sla_days')" :hint="__('employee_relations::cases.hints.investigation_sla_days')" :error="$errors->first('typeForm.investigation_sla_days')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model="typeForm.investigation_sla_days" type="number" min="1" class="hrm-num" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('employee_relations::cases.fields.type_description')" :error="$errors->first('typeForm.description')" containerClass="!space-y-0" :labelClass="$labelClass">
                        <x-ui.input wire:model="typeForm.description" type="text" />
                    </x-ui.input-shell>
                    <div class="flex flex-wrap items-center gap-4 sm:col-span-3">
                        <x-checkbox name="typeForm.is_disciplinary" model="typeForm.is_disciplinary">{{ __('employee_relations::cases.fields.is_disciplinary') }}</x-checkbox>
                        <x-checkbox name="typeForm.is_active" model="typeForm.is_active">{{ __('employee_relations::cases.fields.is_active') }}</x-checkbox>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-hairline-subtle px-4 py-3">
                    @if ($editingTypeId)
                        <x-pill-button variant="ghost" wire:click="cancelType">{{ __('employee_relations::cases.actions.cancel') }}</x-pill-button>
                    @endif
                    <x-pill-button variant="primary" wire:click="saveType" wire:loading.attr="disabled" wire:target="saveType">{{ __('employee_relations::cases.actions.save') }}</x-pill-button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <x-table.tbl :headers="[
                    __('employee_relations::cases.fields.type_name'),
                    __('employee_relations::cases.fields.investigation_sla_days'),
                    __('employee_relations::cases.columns.cases_count'),
                    __('employee_relations::cases.columns.state'),
                    '',
                ]">
                    @foreach ($this->types as $type)
                        <tr wire:key="er-type-{{ $type->id }}">
                            <x-table.td standart-width>
                                <p class="text-[13px] font-medium text-ink">{{ $type->name }}</p>
                                @if ($type->is_disciplinary)
                                    <p class="text-[11px] text-ink-faint">{{ __('employee_relations::cases.fields.is_disciplinary') }}</p>
                                @endif
                            </x-table.td>
                            <x-table.td><span class="hrm-num">{{ $type->investigation_sla_days ?? __('employee_relations::cases.labels.default_sla', ['days' => config('employee_relations.sla_days.investigating')]) }}</span></x-table.td>
                            <x-table.td><span class="hrm-num">{{ $type->cases_count }}</span></x-table.td>
                            <x-table.td>
                                <x-small-badge :mode="$type->is_active ? 'green' : 'secondary'" dot>{{ $type->is_active ? __('employee_relations::cases.labels.active') : __('employee_relations::cases.labels.inactive') }}</x-small-badge>
                            </x-table.td>
                            <x-table.td is-button>
                                <div class="flex justify-end gap-1.5">
                                    <x-pill-button variant="ghost" wire:click="editType({{ $type->id }})">{{ __('employee_relations::cases.actions.edit') }}</x-pill-button>
                                    <x-pill-button variant="ghost" wire:click="toggleType({{ $type->id }})">{{ $type->is_active ? __('employee_relations::cases.actions.deactivate') : __('employee_relations::cases.actions.activate') }}</x-pill-button>
                                </div>
                            </x-table.td>
                        </tr>
                    @endforeach
                </x-table.tbl>
            </section>
        @endif
    </div>
</div>
