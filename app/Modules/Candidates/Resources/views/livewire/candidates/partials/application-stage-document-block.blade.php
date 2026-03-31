@if ($this->documentChecklist)
    <div class="mt-4 rounded-[24px] border border-slate-200 bg-white p-4">
        <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
            {{ __('candidates::recruitment.titles.document_requirements') }}
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($this->documentChecklist as $item)
                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ __('candidates::recruitment.document_checklists.'.$item) }}
                </span>
            @endforeach
        </div>

        <div class="mt-4 grid gap-3">
            @foreach ($this->documentChecklist as $item)
                <div class="rounded-[20px] border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-slate-900">
                                {{ __('candidates::recruitment.document_checklists.'.$item) }}
                            </div>
                            <div class="mt-3 flex flex-col">
                                <x-label for="form.document_items.{{ $item }}.note">{{ __('candidates::recruitment.labels.document_note') }}</x-label>
                                <x-textarea mode="gray" name="form.document_items.{{ $item }}.note" wire:model="form.document_items.{{ $item }}.note"></x-textarea>
                                @error('form.document_items.'.$item.'.note') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        </div>
                        <div class="shrink-0 pt-1">
                            <label class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">
                                <input type="checkbox" wire:model.live="form.document_items.{{ $item }}.is_provided" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-300" />
                                <span>{{ __('candidates::recruitment.labels.document_provided') }}</span>
                            </label>
                            @error('form.document_items.'.$item.'.is_provided') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-white p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ __('candidates::recruitment.labels.stage_document_upload') }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ __('candidates::recruitment.labels.stage_document_upload_hint') }}</div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="file" wire:model="uploadedDocumentFiles.{{ $item }}" multiple class="block text-sm text-slate-500 file:mr-4 file:rounded-full file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                                <button type="button" wire:click="uploadStageDocument('{{ $item }}')" class="inline-flex h-10 items-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                                    {{ __('candidates::recruitment.actions.upload_stage_document') }}
                                </button>
                            </div>
                        </div>
                        @error('uploadedDocumentFiles.'.$item) <x-validation>{{ $message }}</x-validation> @enderror
                        @error('uploadedDocumentFiles.'.$item.'.*') <x-validation>{{ $message }}</x-validation> @enderror

                        @if (! empty($this->currentStageDocumentsByKey[$item]))
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($this->currentStageDocumentsByKey[$item] as $uploadedDocument)
                                    <a href="{{ route('candidates.documents.download', $uploadedDocument) }}" class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-slate-300">
                                        {{ $uploadedDocument->display_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
