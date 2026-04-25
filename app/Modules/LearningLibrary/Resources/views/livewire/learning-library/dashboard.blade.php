<div class="space-y-6 px-6 py-6">
    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('ui::menu.items.learning_library') }}</x-ui.field-label>
            <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ __('learning-library::dashboard.title') }}</h1>
            <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('learning-library::dashboard.description') }}</p>
        </div>

        @php

            $summary = $activeTab === 'general' ? $this->generalPayload['summary'] : $this->summaryPayload['summary'];

        @endphp
        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
            @foreach (['asset_total', 'required_assets', 'active_assets', 'auto_assign_assets', 'active_assignments', 'completed_assignments', 'overdue_assignments'] as $metric)
                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('learning-library::dashboard.summary.'.$metric) }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $summary[$metric] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm">
        <x-filter.nav class="min-w-0">
            <x-filter.item wire:click.prevent="switchTab('general')" :active="$activeTab === 'general'">
                {{ __('learning-library::dashboard.tabs.general') }}
            </x-filter.item>
            <x-filter.item wire:click.prevent="switchTab('library')" :active="$activeTab === 'library'">
                {{ __('learning-library::dashboard.tabs.library') }}
            </x-filter.item>
            <x-filter.item wire:click.prevent="switchTab('reports')" :active="$activeTab === 'reports'">
                {{ __('learning-library::dashboard.tabs.reports') }}
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
            <p class="text-sm text-zinc-400">{{ __('learning-library::dashboard.messages.loading_tab') }}</p>
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
                        <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('learning-library::dashboard.sections.create_asset') }}</x-ui.field-label>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <x-ui.input-shell :label="__('learning-library::dashboard.fields.asset_title')" :error="$errors->first('assetForm.title')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="assetForm.title" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('learning-library::dashboard.fields.content_type')" :error="$errors->first('assetForm.content_type')" labelClass="tracking-tight text-zinc-500">
                                <select wire:model.live="assetForm.content_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                                    @foreach (['video', 'presentation', 'pdf', 'link', 'other'] as $type)
                                        <option value="{{ $type }}">{{ __('personnel::my_hr.learning.content_types.'.$type) }}</option>
                                    @endforeach
                                </select>
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('learning-library::dashboard.fields.version')" :error="$errors->first('assetForm.version')" labelClass="tracking-tight text-zinc-500">
                                <input wire:model.live="assetForm.version" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('learning-library::dashboard.fields.visibility')" :error="$errors->first('assetForm.visibility')" labelClass="tracking-tight text-zinc-500">
                                <select wire:model.live="assetForm.visibility" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                                    @foreach (['internal', 'public'] as $visibility)
                                        <option value="{{ $visibility }}">{{ __('personnel::my_hr.learning_admin.visibility.'.$visibility) }}</option>
                                    @endforeach
                                </select>
                            </x-ui.input-shell>
                            <div class="md:col-span-2">
                                <x-ui.input-shell :label="__('learning-library::dashboard.fields.description')" :error="$errors->first('assetForm.description')" labelClass="tracking-tight text-zinc-500">
                                    <textarea wire:model.live="assetForm.description" rows="4" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none"></textarea>
                                </x-ui.input-shell>
                            </div>
                            <x-ui.file-upload-shell wire:model="assetUpload" :label="__('learning-library::dashboard.fields.file')" :error="$errors->first('assetUpload')" :upload="$assetUpload" />
                            <div class="space-y-4">
                                <x-ui.input-shell :label="__('learning-library::dashboard.fields.estimated_minutes')" :error="$errors->first('assetForm.estimated_minutes')" labelClass="tracking-tight text-zinc-500">
                                    <input wire:model.live="assetForm.estimated_minutes" type="number" min="1" max="600" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none" />
                                </x-ui.input-shell>
                                <x-ui.input-shell :label="__('learning-library::dashboard.fields.external_url')" :error="$errors->first('assetForm.external_url')" labelClass="tracking-tight text-zinc-500">
                                    <input wire:model.live="assetForm.external_url" type="url" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none" />
                                </x-ui.input-shell>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                <input wire:model.live="assetForm.is_active" type="checkbox" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-300" />
                                {{ __('learning-library::dashboard.fields.is_active') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                <input wire:model.live="assetForm.auto_assign_new_hires" type="checkbox" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-300" />
                                {{ __('learning-library::dashboard.fields.auto_assign_new_hires') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                <input wire:model.live="assetForm.is_required" type="checkbox" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-300" />
                                {{ __('learning-library::dashboard.fields.is_required') }}
                            </label>
                        </div>
                        <div class="mt-5">
                            <button type="button" wire:click="saveAsset" wire:loading.attr="disabled" wire:target="saveAsset" class="inline-flex items-center justify-center rounded-[22px] bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800 disabled:opacity-60">
                                {{ __('learning-library::dashboard.actions.save_asset') }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('learning-library::dashboard.sections.recent_assignments') }}</x-ui.field-label>
                                <p class="mt-1 text-sm text-zinc-500">{{ __('learning-library::dashboard.messages.empty_assignments') }}</p>
                            </div>
                        </div>

                        @if ($payload['recent_assignments']->isEmpty())
                            <p class="mt-4 text-sm text-zinc-500">{{ __('learning-library::dashboard.messages.empty_assignments') }}</p>
                        @else
                            <div class="mt-4 space-y-3">
                                @foreach ($payload['recent_assignments'] as $assignment)
                                    <div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                                        <div class="space-y-4">
                                            <div class="space-y-2">
                                                <h3 class="text-base font-semibold tracking-tight text-zinc-950">{{ $assignment['asset'] }}</h3>
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
                                                @if ($assignment['completed_at'] !== '—')
                                                    <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium uppercase tracking-tight text-zinc-700">{{ $assignment['completed_at'] }}</span>
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
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('learning-library::dashboard.sections.assign_asset') }}</x-ui.field-label>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-zinc-600">{{ __('learning-library::dashboard.messages.selection_hint') }}</p>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-zinc-500">{{ __('learning-library::dashboard.messages.rule_builder_hint') }}</p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <x-ui.input-shell :label="__('learning-library::dashboard.fields.asset')" :error="$errors->first('assignmentForm.asset_id')" labelClass="tracking-tight text-zinc-500">
                            <select wire:model.live="assignmentForm.asset_id" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                                <option value="">---</option>
                                @foreach ($payload['assignment_assets'] as $asset)
                                    <option value="{{ $asset['id'] }}">{{ $asset['title'] }} · {{ $asset['type'] }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('learning-library::dashboard.fields.due_at')" :error="$errors->first('assignmentForm.due_at')" labelClass="tracking-tight text-zinc-500">
                            <input wire:model.live="assignmentForm.due_at" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none" />
                        </x-ui.input-shell>
                    </div>

                    <x-library.bulk-target-builder
                        translation-ns="learning-library::dashboard"
                        :payload="$payload"
                        :selected-structure-ids="$selectedStructureIds"
                        :selected-position-ids="$selectedPositionIds"
                        :selected-personnel-ids="$selectedPersonnelIds"
                        :assignment-form="$assignmentForm"
                    />

                    <div class="mt-5">
                        <button type="button" wire:click="assignSelected" wire:loading.attr="disabled" wire:target="assignSelected" class="inline-flex items-center justify-center rounded-[22px] bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800 disabled:opacity-60">
                            {{ __('learning-library::dashboard.actions.assign_selected') }}
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
                        translation-ns="learning-library::dashboard"
                        section-key="assets"
                        search-model="searchAsset"
                        search-field="search_asset"
                        search-placeholder-key="search_asset_placeholder"
                        search-hint-key="asset_search_hint"
                        :actions="$this->exportActions"
                    />
                </div>

                <div class="space-y-4">
                    @forelse ($payload['assets'] as $asset)
                        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ $asset['title'] }}</h3>
                                        <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ $asset['type'] }}</span>
                                        <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">v{{ $asset['version'] }}</span>
                                        @if ($asset['required'])
                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">{{ __('learning-library::dashboard.fields.is_required') }}</span>
                                        @endif
                                        @if ($asset['auto_assign_new_hires'])
                                            <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700">{{ __('learning-library::dashboard.fields.auto_assign_new_hires') }}</span>
                                        @endif
                                        @if ($asset['is_archived'])
                                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">{{ __('learning-library::dashboard.fields.archived') }}</span>
                                        @endif
                                    </div>
                                    @if (! blank($asset['estimated_minutes']))
                                        <p class="text-sm text-zinc-500">{{ __('learning-library::dashboard.fields.estimated_minutes') }}: {{ $asset['estimated_minutes'] }}</p>
                                    @endif
                                    @if ($asset['compare_summary'] !== [])
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($asset['compare_summary'] as $change)
                                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ $change }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if ($asset['content_url'])
                                        <a href="{{ $asset['content_url'] }}" target="_blank" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold tracking-tight text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">{{ __('learning-library::dashboard.actions.open_asset') }}</a>
                                    @endif
                                    @can('manage-employee-content-library')
                                        <button type="button" wire:click="prepareNextAssetVersion({{ $asset['id'] }})" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold tracking-tight text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">{{ __('learning-library::dashboard.actions.new_version') }}</button>
                                        <button type="button" wire:click="toggleAssetActive({{ $asset['id'] }})" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold tracking-tight text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">{{ $asset['toggle_active_label'] }}</button>
                                        <button type="button" wire:click="toggleAssetArchived({{ $asset['id'] }})" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold tracking-tight text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">{{ $asset['is_archived'] ? __('learning-library::dashboard.actions.restore_asset') : __('learning-library::dashboard.actions.archive_asset') }}</button>
                                    @endcan
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <x-ui.field-label as="div" class="tracking-tight">{{ __('learning-library::dashboard.summary.active_assignments') }}</x-ui.field-label>
                                    <p class="mt-2 text-base font-semibold tracking-tight text-zinc-950">{{ $asset['assignments_count'] }}</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <x-ui.field-label as="div" class="tracking-tight">{{ __('learning-library::dashboard.summary.completed_assignments') }}</x-ui.field-label>
                                    <p class="mt-2 text-base font-semibold tracking-tight text-zinc-950">{{ $asset['completed_assignments_count'] }}</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                    <x-ui.field-label as="div" class="tracking-tight">{{ __('learning-library::dashboard.summary.overdue_assignments') }}</x-ui.field-label>
                                    <p class="mt-2 text-base font-semibold tracking-tight text-zinc-950">{{ $asset['overdue_assignments_count'] }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                            <p class="text-sm text-zinc-500">{{ __('learning-library::dashboard.messages.empty_assets') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            @php
                $payload = $this->reportsPayload;
            @endphp
            <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('learning-library::dashboard.sections.reports') }}</x-ui.field-label>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-zinc-500">{{ __('learning-library::dashboard.messages.report_hint') }}</p>
                <div class="mt-5">
                    <x-library.analytics-grid translation-ns="learning-library::dashboard" :analytics="$payload['analytics']" />
                </div>
            </div>
        @endif
    </div>
</div>
