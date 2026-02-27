<div class="flex flex-col space-y-8">
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center">
        <div class="sm:col-span-2 flex items-end space-x-2">
            <div class="w-full">
                <div class="flex items-center space-x-2">
                    <x-label for="types.name">{{ __('Name') }}</x-label>
                    @error('types.name')
                        <x-validation>(* {{ $message }} )</x-validation>
                    @enderror
                </div>
                <x-livewire-input mode="gray" name="types.name" wire:model="types.name"></x-livewire-input>
            </div>
            <button class="rounded-lg shadow-sm bg-teal-500 text-slate-100 px-6 py-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-teal-600 flex-none"
                    wire:click="addType"
            >
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
                <span>{{ __('Add') }}</span>
            </button>
        </div>
    </div>

    <div class="flex flex-col space-y-2">
        @forelse($_order_types as $_type)
            <div class="flex items-center justify-between space-x-2 px-4 py-3 bg-slate-100 rounded-xl shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-slate-900">
                        {{ $loop->iteration }}.
                    </span>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-slate-600">
                            {{ $_type->name }}
                        </span>
                        @if($selectedType == $_type->id)
                            <x-livewire-input mode="default" name="types.name" wire:model="types.name"></x-livewire-input>
                            <button class="rounded-lg shadow-sm bg-green-100 p-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-green-200"
                                    wire:click="updateModel"
                            >
                                <x-icons.check-simple-icon color="text-green-500" hover="text-green-600"></x-icons.check-simple-icon>
                            </button>
                            <button class="rounded-lg shadow-sm bg-rose-100 p-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-rose-200"
                                    wire:click="cancelUpdate"
                            >
                                <x-icons.close-icon color="text-rose-500" hover="text-rose-600"></x-icons.close-icon>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button class="h-8 px-1 rounded-lg hover:bg-sky-50 hover:shadow-sm font-medium text-xs text-sky-700 transition-all duration-300 flex justify-center items-center space-x-2"
                            wire:click.prevent="openUiConfig({{ $_type->id }})"
                    >
                        <x-icons.layout-icon color="text-sky-500" hover="text-sky-600"></x-icons.layout-icon>
                        <span>{{ __('UI config') }}</span>
                    </button>
                    <button class="w-8 h-8 px-1 py-1 rounded-lg hover:bg-emerald-50 hover:shadow-sm font-medium text-sm flex justify-center items-center"
                            wire:click="editType({{ $_type->id }})"
                    >
                        <x-icons.edit-icon color="text-emerald-500" hover="text-emerald-600"></x-icons.edit-icon>
                    </button>
                    <button class="w-8 h-8 px-1 py-1 rounded-lg hover:bg-rose-50 hover:shadow-sm font-medium text-sm flex justify-center items-center"
                            wire:click="removeType({{ $_type->id }})"
                            wire:confirm="{{ __('Are you sure you want to delete?') }}"
                    >
                        <x-icons.backspace-icon color="text-rose-500" hover="text-rose-600"></x-icons.backspace-icon>
                    </button>
                </div>
            </div>
        @empty
            <div class="flex justify-start items-center px-4 py-3 font-medium bg-gray-100 rounded-lg text-gray-500 text-base">
                <span>{{ __('No data exists.') }}</span>
            </div>
        @endforelse
    </div>

    @if($uiConfigOrderTypeId)
        @php
            $activeType = collect($_order_types)->firstWhere('id', $uiConfigOrderTypeId);
            $hasSelectedVersion = filled($uiConfigVersionId);
            $coverageInspectable = (bool) ($uiPlaceholderCoverage['inspectable'] ?? false);
            $missingCount = count($uiPlaceholderCoverage['missing_placeholders'] ?? []);
            $orphanCount = count($uiPlaceholderCoverage['orphan_mappings'] ?? []);
            $hasTemplatePath = filled($uiPlaceholderCoverage['template_path'] ?? null);

            $publishChecks = [
                ['label' => __('Version selected'), 'ok' => $hasSelectedVersion, 'fail' => __('Select a version from versions table.')],
                ['label' => __('Template file attached'), 'ok' => $hasTemplatePath, 'fail' => __('Upload/attach DOCX template to this version.')],
                ['label' => __('Coverage scan available'), 'ok' => $coverageInspectable, 'fail' => __('Coverage is unavailable for this version.')],
                ['label' => __('No missing mappings'), 'ok' => $coverageInspectable && $missingCount === 0, 'fail' => __('Resolve missing placeholders before publish.')],
            ];

            $publishBlockedMessages = collect($publishChecks)
                ->filter(fn ($check) => !($check['ok'] ?? false))
                ->pluck('fail')
                ->values()
                ->all();

            $publishReady = count($publishBlockedMessages) === 0;
        @endphp

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex flex-col">
                    <h3 class="text-sm font-semibold text-slate-700">
                        {{ __('UI config editor') }}
                    </h3>
                    <p class="text-xs text-slate-500">
                        {{ $activeType?->name ?? '' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            class="h-8 px-3 rounded-lg bg-sky-100 hover:bg-sky-200 text-xs font-medium text-sky-800 transition-colors"
                            wire:click.prevent="createUiDraftVersion"
                    >
                        {{ __('Create draft') }}
                    </button>
                    <button type="button"
                            class="h-8 px-3 rounded-lg bg-amber-100 hover:bg-amber-200 text-xs font-medium text-amber-800 transition-colors"
                            wire:click.prevent="bootstrapUiConfigMetadata"
                    >
                        {{ __('Generate metadata') }}
                    </button>
                    <button class="h-8 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700 transition-colors"
                            wire:click="closeUiConfig"
                    >
                        {{ __('Close') }}
                    </button>
                </div>
            </div>

            <x-orders.publish-readiness
                :ready="$publishReady"
                :checks="$publishChecks"
                :blocked-messages="$publishBlockedMessages"
            />

            @if(!empty($uiConfigVersions))
                <div class="space-y-2">
                    <h4 class="text-sm font-semibold text-slate-700">{{ __('Template versions') }}</h4>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="text-slate-500 bg-slate-50 border-b border-slate-200">
                                    <th class="py-2 px-2 min-w-[80px]">{{ __('Version') }}</th>
                                    <th class="py-2 px-2 min-w-[100px]">{{ __('Status') }}</th>
                                    <th class="py-2 px-2 min-w-[90px]">{{ __('Active') }}</th>
                                    <th class="py-2 px-2 min-w-[150px]">{{ __('Published at') }}</th>
                                    <th class="py-2 px-2 min-w-[220px]">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($uiConfigVersions as $version)
                                    <tr @class([
                                        'border-b border-slate-100',
                                        'bg-sky-50/50' => (int) $uiConfigVersionId === (int) ($version['id'] ?? 0),
                                    ])>
                                        <td class="py-2 px-2 font-medium text-slate-700">v{{ (int) ($version['version_no'] ?? 0) }}</td>
                                        <td class="py-2 px-2">{{ (string) ($version['status'] ?? '-') }}</td>
                                        <td class="py-2 px-2">
                                            @if(!empty($version['is_active']))
                                                <span class="text-emerald-700">{{ __('Yes') }}</span>
                                            @else
                                                <span class="text-slate-500">{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-2">{{ (string) ($version['published_at'] ?? '-') }}</td>
                                        <td class="py-2 px-2">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                        class="h-7 px-2 rounded-md bg-slate-100 hover:bg-slate-200 text-[11px] font-medium text-slate-700 transition-colors"
                                                        wire:click="openUiConfig({{ (int) $uiConfigOrderTypeId }}, {{ (int) ($version['id'] ?? 0) }})"
                                                >
                                                    {{ __('Edit') }}
                                                </button>

                                                @if(empty($version['is_active']))
                                                    @php
                                                        $isCurrentVersionRow = (int) ($version['id'] ?? 0) === (int) $uiConfigVersionId;
                                                        $publishDisabled = $isCurrentVersionRow && !$publishReady;
                                                    @endphp
                                                    <button type="button"
                                                            @disabled($publishDisabled)
                                                            class="h-7 px-2 rounded-md bg-emerald-100 hover:bg-emerald-200 text-[11px] font-medium text-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                            wire:click="publishUiConfigVersion({{ (int) ($version['id'] ?? 0) }})"
                                                    >
                                                        {{ __('Publish') }}
                                                    </button>
                                                @endif

                                                @if((string) ($version['status'] ?? '') === 'published' && empty($version['is_active']))
                                                    <button type="button"
                                                            class="h-7 px-2 rounded-md bg-violet-100 hover:bg-violet-200 text-[11px] font-medium text-violet-700 transition-colors"
                                                            wire:click="rollbackUiConfigVersion({{ (int) ($version['id'] ?? 0) }})"
                                                    >
                                                        {{ __('Rollback') }}
                                                    </button>
                                                @endif

                                                @if(in_array((string) ($version['status'] ?? ''), ['draft', 'published'], true) && empty($version['is_active']))
                                                    <button type="button"
                                                            class="h-7 px-2 rounded-md bg-rose-100 hover:bg-rose-200 text-[11px] font-medium text-rose-700 transition-colors"
                                                            wire:click="deleteUiDraftVersion({{ (int) ($version['id'] ?? 0) }})"
                                                            wire:confirm="{{ __('Are you sure you want to delete this version?') }}"
                                                    >
                                                        {{ __('Delete') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if(!empty($uiPlaceholderCoverage))
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-2">
                    <div class="flex flex-col">
                        <h4 class="text-sm font-semibold text-slate-700">{{ __('Placeholder coverage') }}</h4>
                        <p class="text-xs text-slate-500">{{ __('Checks placeholders used in template DOCX against mappings in this version.') }}</p>
                    </div>

                    @if(!empty($uiPlaceholderCoverage['inspectable']))
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 text-xs">
                            <div class="rounded-md border border-slate-200 bg-white px-2 py-1.5">
                                <span class="text-slate-500">{{ __('Template placeholders') }}</span>
                                <div class="font-semibold text-slate-800">{{ count($uiPlaceholderCoverage['template_placeholders'] ?? []) }}</div>
                            </div>
                            <div class="rounded-md border border-slate-200 bg-white px-2 py-1.5">
                                <span class="text-slate-500">{{ __('Mapped placeholders') }}</span>
                                <div class="font-semibold text-slate-800">{{ count($uiPlaceholderCoverage['mapped_placeholders'] ?? []) }}</div>
                            </div>
                            <div class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1.5">
                                <span class="text-rose-600">{{ __('Missing mappings') }}</span>
                                <div class="font-semibold text-rose-700">{{ count($uiPlaceholderCoverage['missing_placeholders'] ?? []) }}</div>
                            </div>
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5">
                                <span class="text-amber-700">{{ __('Orphan mappings') }}</span>
                                <div class="font-semibold text-amber-800">{{ count($uiPlaceholderCoverage['orphan_mappings'] ?? []) }}</div>
                            </div>
                        </div>

                        @if(!empty($uiPlaceholderCoverage['missing_placeholders']))
                            <div class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1.5 text-xs text-rose-700">
                                <span class="font-semibold">{{ __('Missing placeholders') }}:</span>
                                {{ implode(', ', $uiPlaceholderCoverage['missing_placeholders']) }}
                            </div>
                        @endif

                        @if(!empty($uiPlaceholderCoverage['orphan_mappings']))
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5 text-xs text-amber-800">
                                <span class="font-semibold">{{ __('Orphan mappings') }}:</span>
                                {{ implode(', ', $uiPlaceholderCoverage['orphan_mappings']) }}
                            </div>
                        @endif

                        <div class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-600">
                            {{ __('Coverage checks DOCX scalar placeholders. Row mappings are not counted as orphan.') }}
                        </div>

                        @if($orphanCount > 0)
                            <div class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-600">
                                {{ __('Orphan mappings do not block publish, but cleanup is recommended.') }}
                            </div>
                        @endif
                    @else
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5 text-xs text-amber-800">
                            {{ __('Template placeholder scan is unavailable for this version.') }}
                        </div>
                    @endif
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-2">
                <div class="text-xs font-semibold text-slate-700">{{ __('Add metadata field') }}</div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                    <div class="md:col-span-2">
                        <input type="text"
                               placeholder="field_key"
                               class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 focus:border-primary focus:ring-0"
                               wire:model.defer="newFieldKey"/>
                    </div>
                    <div class="md:col-span-2">
                        <input type="text"
                               placeholder="{{ __('Label') }}"
                               class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 focus:border-primary focus:ring-0"
                               wire:model.defer="newFieldLabel"/>
                    </div>
                    <div class="md:col-span-2">
                        <input type="text"
                               placeholder="{{ __('Alias field') }}"
                               class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 focus:border-primary focus:ring-0"
                               wire:model.defer="newFieldAlias"/>
                    </div>
                    <div class="md:col-span-2">
                        <select class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 focus:border-primary focus:ring-0"
                                wire:model.defer="newFieldInput">
                            @foreach($uiInputTypes as $inputValue => $inputLabel)
                                <option value="{{ $inputValue }}">{{ $inputLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <input type="text"
                               placeholder="_personnels"
                               class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 focus:border-primary focus:ring-0"
                               wire:model.defer="newFieldModel"/>
                    </div>
                    <div class="md:col-span-2">
                        <input type="text"
                               placeholder="personnel"
                               class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 focus:border-primary focus:ring-0"
                               wire:model.defer="newFieldSelectedName"/>
                    </div>
                    <div class="md:col-span-3">
                        <input type="text"
                               placeholder="search.personnel"
                               class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 focus:border-primary focus:ring-0"
                               wire:model.defer="newFieldSearchField"/>
                    </div>
                    <div class="md:col-span-3">
                        <input type="text"
                               placeholder="nullable|string"
                               class="w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 focus:border-primary focus:ring-0"
                               wire:model.defer="newFieldRules"/>
                    </div>
                    <div class="md:col-span-2 flex items-center">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox"
                                   class="rounded border-slate-300 text-primary focus:ring-primary/20"
                                   wire:model.defer="newFieldRequired"/>
                            <span>{{ __('Required') }}</span>
                        </label>
                    </div>
                    <div class="md:col-span-4 flex items-center justify-end">
                        <button type="button"
                                class="h-8 px-3 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-xs font-medium text-emerald-800 transition-colors"
                                wire:click="addUiMetadataField"
                        >
                            {{ __('Add field') }}
                        </button>
                    </div>
                </div>
            </div>

            @if(!empty($uiConfigFieldMeta))
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-2 min-w-[170px]">{{ __('Field') }}</th>
                                <th class="py-2 px-2 min-w-[140px]">{{ __('Field key') }}</th>
                                <th class="py-2 px-2 min-w-[150px]">{{ __('Input') }}</th>
                                <th class="py-2 px-2 min-w-[140px]">{{ __('Model') }}</th>
                                <th class="py-2 px-2 min-w-[140px]">{{ __('Selected name') }}</th>
                                <th class="py-2 px-2 min-w-[160px]">{{ __('Search field') }}</th>
                                <th class="py-2 px-2 min-w-[90px]">{{ __('Required') }}</th>
                                <th class="py-2 px-2 min-w-[170px]">{{ __('Rules') }}</th>
                                <th class="py-2 px-2 min-w-[120px]">{{ __('Group') }}</th>
                                <th class="py-2 px-2 min-w-[120px]">{{ __('Group title') }}</th>
                                <th class="py-2 px-2 min-w-[90px]">{{ __('Group order') }}</th>
                                <th class="py-2 px-2 min-w-[90px]">{{ __('Field order') }}</th>
                                <th class="py-2 px-2 min-w-[180px]">{{ __('Grid cols') }}</th>
                                <th class="py-2 pl-2 min-w-[180px]">{{ __('Col span') }}</th>
                                <th class="py-2 pl-2 min-w-[80px]">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($uiConfigFieldMeta as $field)
                                <tr class="border-b border-slate-100 align-top">
                                    <td class="py-2 pr-2">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-700">{{ $field['label'] }}</span>
                                            <span class="text-slate-400">{{ $field['field_key'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="text"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.field"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <select
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.input"
                                        >
                                            @foreach($uiInputTypes as $inputValue => $inputLabel)
                                                <option value="{{ $inputValue }}">{{ $inputLabel }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="text"
                                            placeholder="_personnels"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.model"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="text"
                                            placeholder="personnel"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.selectedName"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="text"
                                            placeholder="search.personnel"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.searchField"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <label class="inline-flex items-center gap-2 text-slate-700">
                                            <input
                                                type="checkbox"
                                                class="rounded border-slate-300 text-primary focus:ring-primary/20"
                                                wire:model.defer="uiConfigDraft.{{ $field['id'] }}.required"
                                            />
                                            <span class="text-xs">{{ __('Yes') }}</span>
                                        </label>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="text"
                                            placeholder="required|int"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.rules"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="text"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.group"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="text"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.group_title"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="number"
                                            min="0"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.group_order"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            type="number"
                                            min="0"
                                            class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                            wire:model.defer="uiConfigDraft.{{ $field['id'] }}.field_order"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <div class="grid grid-cols-3 gap-1">
                                            <input type="number" min="1" placeholder="d" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0" wire:model.defer="uiConfigDraft.{{ $field['id'] }}.grid_cols_default"/>
                                            <input type="number" min="1" placeholder="sm" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0" wire:model.defer="uiConfigDraft.{{ $field['id'] }}.grid_cols_sm"/>
                                            <input type="number" min="1" placeholder="md" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0" wire:model.defer="uiConfigDraft.{{ $field['id'] }}.grid_cols_md"/>
                                        </div>
                                    </td>
                                    <td class="py-2 pl-2">
                                        <div class="grid grid-cols-3 gap-1">
                                            <input type="number" min="1" placeholder="d" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0" wire:model.defer="uiConfigDraft.{{ $field['id'] }}.col_span_default"/>
                                            <input type="number" min="1" placeholder="sm" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0" wire:model.defer="uiConfigDraft.{{ $field['id'] }}.col_span_sm"/>
                                            <input type="number" min="1" placeholder="md" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0" wire:model.defer="uiConfigDraft.{{ $field['id'] }}.col_span_md"/>
                                        </div>
                                    </td>
                                    <td class="py-2 pl-2">
                                        <button
                                            type="button"
                                            class="w-8 h-8 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-600 transition-colors"
                                            wire:click="removeUiMetadataField({{ $field['id'] }})"
                                            wire:confirm="{{ __('Are you sure you want to delete?') }}"
                                        >
                                            ✕
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-3 py-2 text-sm flex items-center justify-between gap-3">
                    <span>{{ __('This template version has no metadata fields yet. Use Generate metadata to build fields from legacy dynamic definitions.') }}</span>
                </div>
            @endif

            <div class="space-y-2">
                <div class="flex flex-col">
                    <h4 class="text-sm font-semibold text-slate-700">{{ __('Section blocks') }}</h4>
                    <p class="text-xs text-slate-500">{{ __('Control visibility and order for template sections.') }}</p>
                    <p class="text-[11px] text-slate-400">{{ __('row_fields is the main dynamic-fields container for each component row. Sort controls display order (10, 20, 30...).') }}</p>
                </div>

                @if(!empty($sectionBlocksDraft))
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="text-slate-500 bg-slate-50 border-b border-slate-200">
                                    <th class="py-2 px-2 min-w-[140px]">{{ __('Key') }}</th>
                                    <th class="py-2 px-2 min-w-[180px]">{{ __('Title') }}</th>
                                    <th class="py-2 px-2 min-w-[110px]">{{ __('Enabled') }}</th>
                                    <th class="py-2 px-2 min-w-[110px]">{{ __('Sort') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sectionBlocksDraft as $blockIndex => $block)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-2 px-2">
                                            <input
                                                type="text"
                                                class="w-full rounded-md border border-slate-200 bg-slate-100 px-2 py-1 text-slate-600"
                                                wire:model.defer="sectionBlocksDraft.{{ $blockIndex }}.key"
                                                readonly
                                            />
                                        </td>
                                        <td class="py-2 px-2">
                                            <input
                                                type="text"
                                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                                wire:model.defer="sectionBlocksDraft.{{ $blockIndex }}.title"
                                            />
                                        </td>
                                        <td class="py-2 px-2">
                                            <label class="inline-flex items-center gap-2 text-slate-700">
                                                <input
                                                    type="checkbox"
                                                    class="rounded border-slate-300 text-primary focus:ring-primary/20"
                                                    wire:model.live="sectionBlocksDraft.{{ $blockIndex }}.enabled"
                                                />
                                                <span>{{ __('Visible') }}</span>
                                            </label>
                                        </td>
                                        <td class="py-2 px-2">
                                            <input
                                                type="number"
                                                min="0"
                                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                                wire:model.defer="sectionBlocksDraft.{{ $blockIndex }}.order"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg border border-slate-200 bg-slate-50 text-slate-600 px-3 py-2 text-sm">
                        {{ __('No section blocks configured.') }}
                    </div>
                @endif
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <h4 class="text-sm font-semibold text-slate-700">{{ __('Mapping editor') }}</h4>
                        <p class="text-xs text-slate-500">{{ __('Map placeholders to field keys. Scope row = per component row, scalar = global value.') }}</p>
                    </div>
                    <button type="button"
                            class="h-8 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700 transition-colors"
                            wire:click="addMappingRow"
                    >
                        {{ __('Add mapping') }}
                    </button>
                </div>

                @if(!empty($mappingDraft))
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="text-slate-500 bg-slate-50 border-b border-slate-200">
                                    <th class="py-2 px-2 min-w-[150px]">{{ __('Placeholder') }}</th>
                                    <th class="py-2 px-2 min-w-[150px]">{{ __('Field key') }}</th>
                                    <th class="py-2 px-2 min-w-[120px]">{{ __('Scope') }}</th>
                                    <th class="py-2 px-2 min-w-[90px]">{{ __('Sort') }}</th>
                                    <th class="py-2 px-2 min-w-[260px]">{{ __('Mapping config (JSON)') }}</th>
                                    <th class="py-2 px-2 min-w-[70px]"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mappingDraft as $mappingIndex => $mapping)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-2 px-2">
                                            <input
                                                type="text"
                                                placeholder="$fullname"
                                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                                wire:model.defer="mappingDraft.{{ $mappingIndex }}.placeholder"
                                            />
                                        </td>
                                        <td class="py-2 px-2">
                                            <input
                                                type="text"
                                                placeholder="fullname"
                                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                                wire:model.defer="mappingDraft.{{ $mappingIndex }}.field_key"
                                            />
                                        </td>
                                        <td class="py-2 px-2">
                                            <select
                                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                                wire:model.defer="mappingDraft.{{ $mappingIndex }}.scope"
                                            >
                                                <option value="row">{{ __('row') }}</option>
                                                <option value="scalar">{{ __('scalar') }}</option>
                                            </select>
                                        </td>
                                        <td class="py-2 px-2">
                                            <input
                                                type="number"
                                                min="0"
                                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0"
                                                wire:model.defer="mappingDraft.{{ $mappingIndex }}.order"
                                            />
                                        </td>
                                        <td class="py-2 px-2">
                                            <textarea
                                                rows="1"
                                                placeholder='{"transform":{"type":"date.format","options":{"format":"d.m.Y"}}}'
                                                class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1 focus:border-primary focus:ring-0 font-mono text-[11px]"
                                                wire:model.defer="mappingDraft.{{ $mappingIndex }}.mapping_config_json"
                                            ></textarea>
                                        </td>
                                        <td class="py-2 px-2">
                                            <button type="button"
                                                    class="w-8 h-8 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-600 transition-colors"
                                                    wire:click="removeMappingRow({{ $mappingIndex }})"
                                            >
                                                ✕
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg border border-slate-200 bg-slate-50 text-slate-600 px-3 py-2 text-sm">
                        {{ __('No mappings configured.') }}
                    </div>
                @endif
            </div>

            @error('uiConfigDraft.*')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('newFieldKey')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('newFieldLabel')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('newFieldAlias')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('newFieldInput')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('newFieldModel')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('newFieldSelectedName')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('newFieldSearchField')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('newFieldRules')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @foreach($errors->get('uiConfigDraft.*.field') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @foreach($errors->get('uiConfigDraft.*.input') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @foreach($errors->get('uiConfigDraft.*.model') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @foreach($errors->get('uiConfigDraft.*.selectedName') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @foreach($errors->get('uiConfigDraft.*.searchField') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @foreach($errors->get('uiConfigDraft.*.required') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @foreach($errors->get('uiConfigDraft.*.rules') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @error('sectionBlocksDraft.*')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('mappingDraft.*')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @error('mappingDraft')
                <x-validation>{{ $message }}</x-validation>
            @enderror
            @foreach($errors->get('mappingDraft.*.placeholder') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @foreach($errors->get('mappingDraft.*.field_key') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach
            @foreach($errors->get('mappingDraft.*.mapping_config_json') as $messages)
                @foreach($messages as $message)
                    <x-validation>{{ $message }}</x-validation>
                @endforeach
            @endforeach

            @if(!empty($uiConfigAuditTrail))
                <div class="space-y-2">
                    <h4 class="text-sm font-semibold text-slate-700">{{ __('Recent changes') }}</h4>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 divide-y divide-slate-200 max-h-56 overflow-y-auto">
                        @foreach($uiConfigAuditTrail as $audit)
                            @php
                                $payload = is_array($audit['payload'] ?? null) ? $audit['payload'] : [];
                            @endphp
                            <div class="px-3 py-2 text-xs text-slate-600 flex items-start justify-between gap-3">
                                <div class="flex flex-col">
                                    <span class="font-medium text-slate-700">{{ $this->resolveUiAuditActionLabel((string) ($audit['action'] ?? '')) }}</span>
                                    @if(!empty($payload['field_key']))
                                        <span class="text-slate-500">{{ __('Field') }}: {{ $payload['field_key'] }}</span>
                                    @endif
                                    @if(isset($payload['fields_count']) || isset($payload['mappings_count']) || isset($payload['section_blocks_count']))
                                        <span class="text-slate-500">
                                            {{ __('Fields') }}: {{ (int) ($payload['fields_count'] ?? 0) }},
                                            {{ __('Mappings') }}: {{ (int) ($payload['mappings_count'] ?? 0) }},
                                            {{ __('Sections') }}: {{ (int) ($payload['section_blocks_count'] ?? 0) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-right text-[11px] text-slate-500 shrink-0">
                                    <div>{{ $audit['actor'] ?? __('System') }}</div>
                                    <div>{{ $audit['created_at'] ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end space-x-2">
                <button class="h-9 px-4 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm font-medium text-slate-700 transition-colors"
                        wire:click="closeUiConfig"
                >
                    {{ __('Cancel') }}
                </button>
                <button class="h-9 px-4 rounded-lg bg-black hover:bg-black/80 text-sm font-semibold text-white transition-colors disabled:opacity-50"
                        wire:click="saveUiConfig"
                        wire:loading.attr="disabled"
                >
                    {{ __('Save UI config') }}
                </button>
            </div>
        </div>
    @endif
</div>
