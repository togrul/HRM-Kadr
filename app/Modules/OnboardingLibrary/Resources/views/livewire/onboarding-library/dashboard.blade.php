<div class="space-y-6 px-6 py-6">
    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('ui::menu.items.onboarding_library') }}</x-ui.field-label>
            <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ __('onboarding-library::dashboard.title') }}</h1>
            <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('onboarding-library::dashboard.description') }}</p>
        </div>

        @php

            $summary = $activeTab === 'general' ? $this->generalPayload['summary'] : $this->summaryPayload['summary'];

        @endphp
        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
            @foreach (['template_total', 'required_templates', 'active_templates', 'auto_assign_templates', 'active_assignments', 'acknowledged_assignments', 'overdue_assignments'] as $metric)
                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('onboarding-library::dashboard.summary.'.$metric) }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $summary[$metric] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm">
        <x-filter.nav class="min-w-0">
            <x-filter.item wire:click.prevent="switchTab('general')" :active="$activeTab === 'general'">
                {{ __('onboarding-library::dashboard.tabs.general') }}
            </x-filter.item>
            <x-filter.item wire:click.prevent="switchTab('library')" :active="$activeTab === 'library'">
                {{ __('onboarding-library::dashboard.tabs.library') }}
            </x-filter.item>
            <x-filter.item wire:click.prevent="switchTab('reports')" :active="$activeTab === 'reports'">
                {{ __('onboarding-library::dashboard.tabs.reports') }}
            </x-filter.item>
        </x-filter.nav>
    </div>

    <div wire:loading.flex wire:target="switchTab" class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="w-full animate-pulse space-y-4">
            <div class="h-5 w-40 rounded-full bg-zinc-200"></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="h-32 rounded-[28px] bg-zinc-100"></div>
                <div class="h-48 rounded-[28px] bg-zinc-100"></div>
            </div>
            <p class="text-sm text-zinc-400">{{ __('onboarding-library::dashboard.messages.loading_tab') }}</p>
        </div>
    </div>

    <div wire:loading.remove wire:target="switchTab">
        @if ($activeTab === 'general')
            @php
                $payload = $this->generalPayload;
            @endphp
            <div class="grid gap-6 xl:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
                <div class="space-y-6">
                    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                        <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('onboarding-library::dashboard.sections.create_template') }}</x-ui.field-label>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <x-ui.input-shell :label="__('onboarding-library::dashboard.fields.template_title')" :error="$errors->first('templateForm.title')" labelClass="tracking-tight text-zinc-500">
                                <x-ui.filter-input wire:model.live="templateForm.title" type="text" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('onboarding-library::dashboard.fields.document_type')" :error="$errors->first('templateForm.document_type')" labelClass="tracking-tight text-zinc-500">
                                <x-ui.filter-native-select wire:model.live="templateForm.document_type">
                                    @foreach (['policy', 'internal_regulation', 'job_instruction', 'security_rule', 'welcome_pack', 'other'] as $type)
                                        <option value="{{ $type }}">{{ __('personnel::my_hr.onboarding.document_types.'.$type) }}</option>
                                    @endforeach
                                </x-ui.filter-native-select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('onboarding-library::dashboard.fields.version')" :error="$errors->first('templateForm.version')" labelClass="tracking-tight text-zinc-500">
                                <x-ui.filter-input wire:model.live="templateForm.version" type="text" />
                            </x-ui.input-shell>
                            <x-ui.file-upload-shell wire:model="templateUpload" :label="__('onboarding-library::dashboard.fields.file')" :error="$errors->first('templateUpload')" :upload="$templateUpload" />
                            <x-ui.input-shell :label="__('onboarding-library::dashboard.fields.effective_from')" :error="$errors->first('templateForm.effective_from')" labelClass="tracking-tight text-zinc-500">
                                <x-ui.filter-input wire:model.live="templateForm.effective_from" type="date" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('onboarding-library::dashboard.fields.effective_to')" :error="$errors->first('templateForm.effective_to')" labelClass="tracking-tight text-zinc-500">
                                <x-ui.filter-input wire:model.live="templateForm.effective_to" type="date" />
                            </x-ui.input-shell>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                <input wire:model.live="templateForm.is_required" type="checkbox" class="library-target-checkbox" />
                                {{ __('onboarding-library::dashboard.fields.is_required') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                <input wire:model.live="templateForm.requires_acknowledgement" type="checkbox" class="library-target-checkbox" />
                                {{ __('onboarding-library::dashboard.fields.requires_acknowledgement') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                <input wire:model.live="templateForm.is_active" type="checkbox" class="library-target-checkbox" />
                                {{ __('onboarding-library::dashboard.fields.is_active') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                <input wire:model.live="templateForm.auto_assign_new_hires" type="checkbox" class="library-target-checkbox" />
                                {{ __('onboarding-library::dashboard.fields.auto_assign_new_hires') }}
                            </label>
                        </div>
                        <div class="mt-5">
                            <button type="button" wire:click="saveTemplate" wire:loading.attr="disabled" wire:target="saveTemplate" class="inline-flex items-center justify-center rounded-[22px] bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800 disabled:opacity-60">
                                {{ __('onboarding-library::dashboard.actions.save_template') }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('onboarding-library::dashboard.sections.recent_assignments') }}</x-ui.field-label>
                                <p class="mt-1 text-sm text-zinc-500">{{ __('onboarding-library::dashboard.messages.empty_assignments') }}</p>
                            </div>
                        </div>

                        @if ($payload['recent_assignments']->isEmpty())
                            <p class="mt-4 text-sm text-zinc-500">{{ __('onboarding-library::dashboard.messages.empty_assignments') }}</p>
                        @else
                            <div class="mt-4 space-y-3">
                                @foreach ($payload['recent_assignments'] as $assignment)
                                    <div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                                        <div class="space-y-4">
                                            <div class="space-y-2">
                                                <h3 class="text-base font-semibold tracking-tight text-zinc-950">{{ $assignment['template'] }}</h3>
                                                <p class="text-sm text-zinc-700">{{ $assignment['personnel'] }}</p>
                                                <p class="text-sm text-zinc-500">{{ $assignment['position'] }}</p>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium uppercase tracking-tight text-zinc-700">{{ $assignment['assigned_at'] }}</span>
                                                <span @class([
                                                    'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-medium uppercase tracking-tight',
                                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $assignment['status_mode'] === 'emerald',
                                                    'border-rose-200 bg-rose-50 text-rose-700' => $assignment['status_mode'] === 'rose',
                                                    'border-sky-200 bg-sky-50 text-sky-700' => $assignment['status_mode'] === 'sky',
                                                    'border-zinc-200 bg-white text-zinc-700' => ! in_array($assignment['status_mode'], ['emerald', 'rose', 'sky'], true),
                                                ])>{{ $assignment['status'] }}</span>
                                                @if ($assignment['acknowledged_at'] !== '—')
                                                    <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium uppercase tracking-tight text-zinc-700">{{ $assignment['acknowledged_at'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($payload['recent_assignments']->hasPages())
                                <div class="mt-4">
                                    {{ $payload['recent_assignments']->onEachSide(1)->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm self-start">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('onboarding-library::dashboard.sections.assign_template') }}</x-ui.field-label>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-zinc-600">{{ __('onboarding-library::dashboard.messages.selection_hint') }}</p>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-zinc-500">{{ __('onboarding-library::dashboard.messages.rule_builder_hint') }}</p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <x-ui.input-shell :label="__('onboarding-library::dashboard.fields.template')" :error="$errors->first('assignmentForm.template_id')" labelClass="tracking-tight text-zinc-500">
                            <x-ui.filter-native-select wire:model.live="assignmentForm.template_id">
                                <option value="">---</option>
                                @foreach ($payload['assignment_templates'] as $template)
                                    <option value="{{ $template['id'] }}">{{ $template['title'] }} · v{{ $template['version'] }}</option>
                                @endforeach
                            </x-ui.filter-native-select>
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('onboarding-library::dashboard.fields.due_at')" :error="$errors->first('assignmentForm.due_at')" labelClass="tracking-tight text-zinc-500">
                            <x-ui.filter-input wire:model.live="assignmentForm.due_at" type="date" />
                        </x-ui.input-shell>
                    </div>

                    <x-library.bulk-target-builder
                        translation-ns="onboarding-library::dashboard"
                        :payload="$payload"
                        :selected-structure-ids="$selectedStructureIds"
                        :selected-position-ids="$selectedPositionIds"
                        :selected-personnel-ids="$selectedPersonnelIds"
                        :assignment-form="$assignmentForm"
                    />

                    <div class="mt-5">
                        <button type="button" wire:click="assignSelected" wire:loading.attr="disabled" wire:target="assignSelected" class="inline-flex items-center justify-center rounded-[22px] bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800 disabled:opacity-60">
                            {{ __('onboarding-library::dashboard.actions.assign_selected') }}
                        </button>
                    </div>
                </div>
            </div>
        @elseif ($activeTab === 'library')
            @php
                $payload = $this->libraryPayload;
            @endphp
            <div class="space-y-6">
                <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                    <x-library.browser-toolbar
                        translation-ns="onboarding-library::dashboard"
                        section-key="templates"
                        search-model="searchTemplate"
                        search-field="search_template"
                        search-placeholder-key="search_template_placeholder"
                        search-hint-key="template_search_hint"
                        :actions="$this->exportActions"
                    />
                </div>

                <div class="space-y-4">
                    @forelse ($payload['templates'] as $template)
                        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ $template['title'] }}</h3>
                                        <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ $template['type'] }}</span>
                                        <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">v{{ $template['version'] }}</span>
                                        @if ($template['required'])
                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">{{ __('onboarding-library::dashboard.fields.is_required') }}</span>
                                        @endif
                                        @if ($template['auto_assign_new_hires'])
                                            <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700">{{ __('onboarding-library::dashboard.fields.auto_assign_new_hires') }}</span>
                                        @endif
                                        @if ($template['is_archived'])
                                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">{{ __('onboarding-library::dashboard.fields.archived') }}</span>
                                        @endif
                                    </div>
                                    @if ($template['compare_summary'] !== [])
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($template['compare_summary'] as $change)
                                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ $change }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if ($template['file_url'])
                                        <a href="{{ $template['file_url'] }}" target="_blank" class="inline-flex items-center justify-center rounded-2xl bg-[#f5f5f7] px-4 py-2 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">{{ __('onboarding-library::dashboard.actions.open_file') }}</a>
                                    @endif
                                    @can('manage-onboarding-document-templates')
                                        <button type="button" wire:click="prepareNextTemplateVersion({{ $template['id'] }})" class="inline-flex items-center justify-center rounded-2xl bg-[#f5f5f7] px-4 py-2 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">{{ __('onboarding-library::dashboard.actions.new_version') }}</button>
                                        <button type="button" wire:click="toggleTemplateActive({{ $template['id'] }})" class="inline-flex items-center justify-center rounded-2xl bg-[#f5f5f7] px-4 py-2 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">{{ $template['toggle_active_label'] }}</button>
                                        <button type="button" wire:click="toggleTemplateArchived({{ $template['id'] }})" class="inline-flex items-center justify-center rounded-2xl bg-[#f5f5f7] px-4 py-2 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">{{ $template['is_archived'] ? __('onboarding-library::dashboard.actions.restore_template') : __('onboarding-library::dashboard.actions.archive_template') }}</button>
                                    @endcan
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <x-ui.field-label as="div" class="tracking-tight">{{ __('onboarding-library::dashboard.summary.active_assignments') }}</x-ui.field-label>
                                    <p class="mt-2 text-base font-semibold tracking-tight text-zinc-950">{{ $template['assignments_count'] }}</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <x-ui.field-label as="div" class="tracking-tight">{{ __('onboarding-library::dashboard.summary.acknowledged_assignments') }}</x-ui.field-label>
                                    <p class="mt-2 text-base font-semibold tracking-tight text-zinc-950">{{ $template['acknowledged_assignments_count'] }}</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <x-ui.field-label as="div" class="tracking-tight">{{ __('onboarding-library::dashboard.summary.overdue_assignments') }}</x-ui.field-label>
                                    <p class="mt-2 text-base font-semibold tracking-tight text-zinc-950">{{ $template['overdue_assignments_count'] }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                            <p class="text-sm text-zinc-500">{{ __('onboarding-library::dashboard.messages.empty_templates') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            @php
                $payload = $this->reportsPayload;
            @endphp
            <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('onboarding-library::dashboard.sections.reports') }}</x-ui.field-label>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-zinc-500">{{ __('onboarding-library::dashboard.messages.report_hint') }}</p>
                <div class="mt-5">
                    <x-library.analytics-grid translation-ns="onboarding-library::dashboard" :analytics="$payload['analytics']" />
                </div>
            </div>
        @endif
    </div>
</div>
