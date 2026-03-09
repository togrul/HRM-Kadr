<div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
    <div class="sidemenu-title">
        <h2 class="text-xl font-title font-semibold text-gray-500" id="slide-over-title">
            {{ __('orders::template_onboarding_wizard.title') }}
        </h2>
        <p class="mt-1 text-sm text-zinc-500">
            {{ __('orders::template_onboarding_wizard.subtitle') }}
        </p>
    </div>

    <x-surface-card
        :title="__('orders::template_onboarding_wizard.cards.execution_checklist')"
        class="bg-white shadow-none"
        contentClass="p-3"
    >
        <ol class="space-y-2 text-sm text-zinc-700">
            <li class="flex items-start gap-2">
                <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">1</span>
                <span>{{ __('orders::template_onboarding_wizard.checklist.ensure_template_set') }}</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">2</span>
                <span>{{ __('orders::template_onboarding_wizard.checklist.upload_docx') }}</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">3</span>
                <span>{{ __('orders::template_onboarding_wizard.checklist.generate_metadata') }}</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">4</span>
                <span>{{ __('orders::template_onboarding_wizard.checklist.check_coverage') }}</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">5</span>
                <span>{{ __('orders::template_onboarding_wizard.checklist.preview_publish') }}</span>
            </li>
        </ol>
    </x-surface-card>

    <x-surface-card
        :title="__('orders::template_onboarding_wizard.cards.start_from_existing_template')"
        class="bg-white shadow-none"
        contentClass="p-3"
    >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <div>
                <x-ui.select-dropdown
                    :label="__('orders::template_onboarding_wizard.labels.template')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="templateId"
                    :model="$this->templateOptions"
                />
            </div>

            <x-button mode="default" wire:click="ensureTemplateSetsForSelectedTemplate">
                {{ __('orders::template_onboarding_wizard.actions.ensure_sets') }}
            </x-button>

            <x-button mode="black" wire:click="openUiConfigForSelectedTemplate">
                {{ __('orders::template_onboarding_wizard.actions.open_ui_config') }}
            </x-button>
        </div>

        @if(filled($setEnsureResult))
            <p class="mt-2 text-xs text-emerald-700">
                {{ $setEnsureResult }}
            </p>
        @endif
    </x-surface-card>

    <x-surface-card
        :title="__('orders::template_onboarding_wizard.cards.step_type_version_docx')"
        class="bg-white shadow-none"
        contentClass="p-3 space-y-3"
    >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-ui.select-dropdown
                :label="__('orders::template_onboarding_wizard.labels.order_type')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="orderTypeId"
                :model="$this->orderTypeOptions"
            />

            <x-ui.select-dropdown
                :label="__('orders::template_onboarding_wizard.labels.template_version')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="versionId"
                :model="$this->versionOptions"
            />
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button mode="default" wire:click="createDraftVersion">
                {{ __('orders::template_onboarding_wizard.actions.create_draft_version') }}
            </x-button>

            @if(filled($versionResult))
                <span class="text-xs text-emerald-700">{{ $versionResult }}</span>
            @endif
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-3">
            <div class="text-xs font-semibold text-slate-700">{{ __('orders::template_onboarding_wizard.labels.upload_docx_to_selected_version') }}</div>
            <div class="flex flex-col gap-2">
                <div class="w-full rounded-lg bg-white p-2">
                    <div class="flex flex-col gap-2" x-data="{ isUploading: false, progress: 0 }"
                         x-on:livewire-upload-start="isUploading = true"
                         x-on:livewire-upload-finish="isUploading = false"
                         x-on:livewire-upload-error="isUploading = false"
                         x-on:livewire-upload-progress="progress = $event.detail.progress">
                        <label class="inline-flex h-10 w-fit cursor-pointer items-center justify-center rounded-md bg-blue-100 px-3 text-sm text-slate-700 shadow-sm hover:bg-blue-200">
                            <span>{{ __('orders::template_onboarding_wizard.labels.choose_docx') }}</span>
                            <input type="file" class="hidden" wire:model="docxFile">
                        </label>

                        <div x-show="isUploading">
                            <progress class="w-full overflow-hidden rounded-lg" max="100" x-bind:value="progress"></progress>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-button mode="black" wire:click="uploadDocxForSelectedVersion">
                        {{ __('orders::template_onboarding_wizard.actions.upload_and_attach') }}
                    </x-button>

                    @if(filled($uploadResult))
                        <span class="text-xs text-emerald-700">{{ $uploadResult }}</span>
                    @endif
                </div>
            </div>

            @error('docxFile')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror

            @if(filled($currentChecksum) || filled($uploadedChecksum))
                <div class="space-y-1 rounded-md border border-slate-200 bg-white p-2 text-xs">
                    @if(filled($currentChecksum))
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-500">{{ __('orders::template_onboarding_wizard.labels.current_checksum') }}</span>
                            <span class="font-mono text-slate-700 break-all">{{ $currentChecksum }}</span>
                        </div>
                    @endif
                    @if(filled($uploadedChecksum))
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-500">{{ __('orders::template_onboarding_wizard.labels.uploaded_checksum') }}</span>
                            <span class="font-mono text-slate-700 break-all">{{ $uploadedChecksum }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-surface-card>

    <x-surface-card
        :title="__('orders::template_onboarding_wizard.cards.step_metadata_coverage')"
        class="bg-white shadow-none"
        contentClass="p-3 space-y-3"
    >
        @php
            $hasTemplate = filled($templateId);
            $hasOrderType = filled($orderTypeId);
            $hasVersion = filled($versionId);
            $hasChecksum = filled($currentChecksum);
            $hasCoverage = !empty($coverage);
            $coverageInspectable = (bool) ($coverage['inspectable'] ?? false);
            $missingCount = count($coverage['missing_placeholders'] ?? []);
            $orphanCount = count($coverage['orphan_mappings'] ?? []);

            $publishChecks = [
                ['label' => __('orders::template_onboarding_wizard.readiness.template_selected'), 'ok' => $hasTemplate, 'fail' => __('orders::template_onboarding_wizard.readiness.fail.template_selected')],
                ['label' => __('orders::template_onboarding_wizard.readiness.order_type_selected'), 'ok' => $hasOrderType, 'fail' => __('orders::template_onboarding_wizard.readiness.fail.order_type_selected')],
                ['label' => __('orders::template_onboarding_wizard.readiness.version_selected'), 'ok' => $hasVersion, 'fail' => __('orders::template_onboarding_wizard.readiness.fail.version_selected')],
                ['label' => __('orders::template_onboarding_wizard.readiness.docx_attached'), 'ok' => $hasChecksum, 'fail' => __('orders::template_onboarding_wizard.readiness.fail.docx_attached')],
                ['label' => __('orders::template_onboarding_wizard.readiness.coverage_scan_runnable'), 'ok' => $coverageInspectable, 'fail' => __('orders::template_onboarding_wizard.readiness.fail.coverage_scan_runnable')],
                ['label' => __('orders::template_onboarding_wizard.readiness.no_missing_mappings'), 'ok' => $hasCoverage && $missingCount === 0, 'fail' => __('orders::template_onboarding_wizard.readiness.fail.no_missing_mappings')],
            ];

            $publishBlockedMessages = collect($publishChecks)
                ->filter(fn ($check) => !($check['ok'] ?? false))
                ->pluck('fail')
                ->values()
                ->all();

            $publishReady = count($publishBlockedMessages) === 0;
        @endphp

        <div class="flex flex-wrap items-center gap-2">
            <x-button mode="default" wire:click="generateMetadataAndMappings">
                {{ __('orders::template_onboarding_wizard.actions.generate_metadata_mappings') }}
            </x-button>

            <x-button mode="gray" wire:click="runCoverageScan">
                {{ __('orders::template_onboarding_wizard.actions.run_coverage') }}
            </x-button>
        </div>

        @if(filled($metadataResult))
            <p class="text-xs text-emerald-700">{{ $metadataResult }}</p>
        @endif

        <x-orders.publish-readiness
            :title="__('orders::template_onboarding_wizard.cards.step_publish_readiness')"
            :ready="$publishReady"
            :checks="$publishChecks"
            :blocked-messages="$publishBlockedMessages"
        />

        @if(!empty($coverage))
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-2">
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-4 text-xs">
                        <div class="rounded-md border border-slate-200 bg-white px-2 py-1.5">
                            <span class="text-slate-500">{{ __('orders::template_onboarding_wizard.labels.template_placeholders') }}</span>
                            <div class="font-semibold text-slate-800">{{ count($coverage['template_placeholders'] ?? []) }}</div>
                        </div>
                    <div class="rounded-md border border-slate-200 bg-white px-2 py-1.5">
                        <span class="text-slate-500">{{ __('orders::template_onboarding_wizard.labels.mapped_placeholders') }}</span>
                        <div class="font-semibold text-slate-800">{{ count($coverage['mapped_placeholders'] ?? []) }}</div>
                    </div>
                    <div class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1.5">
                        <span class="text-rose-600">{{ __('orders::template_onboarding_wizard.labels.missing_mappings') }}</span>
                        <div class="font-semibold text-rose-700">{{ count($coverage['missing_placeholders'] ?? []) }}</div>
                    </div>
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5">
                            <span class="text-amber-700">{{ __('orders::template_onboarding_wizard.labels.orphan_mappings') }}</span>
                            <div class="font-semibold text-amber-800">{{ count($coverage['orphan_mappings'] ?? []) }}</div>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500">
                        {{ __('orders::template_onboarding_wizard.descriptions.coverage_docx_scalar_hint') }}
                    </p>

                @if(!empty($coverage['missing_placeholders']))
                    <div class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1.5 text-xs text-rose-700">
                        <span class="font-semibold">{{ __('orders::template_onboarding_wizard.labels.missing_placeholders') }}:</span>
                        {{ implode(', ', $coverage['missing_placeholders']) }}
                    </div>
                @endif

                @if(!empty($coverage['orphan_mappings']))
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5 text-xs text-amber-800">
                        <span class="font-semibold">{{ __('orders::template_onboarding_wizard.labels.orphan_mappings') }}:</span>
                        {{ implode(', ', $coverage['orphan_mappings']) }}
                    </div>
                @endif

                @if($orphanCount > 0)
                    <div class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-600">
                        {{ __('orders::template_onboarding_wizard.descriptions.orphan_note') }}
                    </div>
                @endif
            </div>
        @endif
    </x-surface-card>

    <x-surface-card
        :title="__('orders::template_onboarding_wizard.cards.step_preview_publish')"
        class="bg-white shadow-none"
        contentClass="p-3 space-y-3"
    >
        <div class="flex flex-wrap items-center gap-2">
            <x-button mode="default" wire:click="runPreviewRender" :disabled="!$publishReady">
                {{ __('orders::template_onboarding_wizard.actions.run_preview_render') }}
            </x-button>

            <x-button mode="gray" wire:click="downloadPreview" :disabled="!$previewSucceeded">
                {{ __('orders::template_onboarding_wizard.actions.download_preview') }}
            </x-button>

            <x-button mode="success" wire:click="publishSelectedVersion" :disabled="!$publishReady || !$previewSucceeded">
                {{ __('orders::template_onboarding_wizard.actions.publish_version') }}
            </x-button>
        </div>

        @if(!$previewSucceeded)
            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                {{ __('orders::template_onboarding_wizard.descriptions.publish_requires_preview') }}
            </div>
        @endif

        @if(filled($previewResult))
            <p class="text-xs text-emerald-700">{{ $previewResult }}</p>
        @endif

        @if($previewSucceeded && filled($previewOutputName))
            <div class="rounded-md border border-slate-200 bg-slate-50 p-2 text-xs text-slate-700">
                <span class="font-semibold">{{ __('orders::template_onboarding_wizard.labels.preview_file') }}:</span>
                <span class="font-mono">{{ $previewOutputName }}</span>
            </div>
        @endif

        @if(filled($publishResult))
            <p class="text-xs text-emerald-700">{{ $publishResult }}</p>
        @endif
    </x-surface-card>

    <x-surface-card
        :title="__('orders::template_onboarding_wizard.cards.roadmap_source')"
        class="bg-white shadow-none"
        contentClass="p-3"
    >
        <p class="text-sm text-zinc-600">
            {{ __('orders::template_onboarding_wizard.descriptions.roadmap_source') }}
        </p>
    </x-surface-card>
</div>
