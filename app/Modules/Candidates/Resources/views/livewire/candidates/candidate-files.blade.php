<div class="flex flex-col space-y-5">
    @php
        $draftTheme = $this->categoryTheme($draft['category'] ?? 'other');
        $draftIcon = $this->categoryIcon($draft['category'] ?? 'other');
        $uploadedIsImage = $uploadedFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
            && filled($uploadedFile->getMimeType())
            && \Illuminate\Support\Str::startsWith((string) $uploadedFile->getMimeType(), 'image/');
        $uploadedPreviewUrl = $uploadedIsImage ? $uploadedFile->temporaryUrl() : null;
    @endphp

    <div class="sidemenu-title">
        <h2 class="font-title text-xl font-semibold text-gray-500" id="slide-over-title">
            {{ $title ?? '' }}
        </h2>
    </div>

    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_60px_-36px_rgba(15,23,42,0.35)]">
        <div class="border-b border-slate-200 bg-gradient-to-br from-slate-50 via-white to-slate-100 px-5 py-5">
            <div class="flex items-start gap-4">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm">
                        <x-icons.files-icon size="w-8 h-8" color="text-white"></x-icons.files-icon>
                    </div>
                    <div class="space-y-2">
                        <div class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-tight text-white">
                            {{ __('candidates::files.labels.archive') }}
                        </div>
                        <h3 class="text-2xl font-semibold text-slate-900">{{ __('candidates::files.titles.all_documents') }}</h3>
                        <p class="max-w-3xl text-sm text-slate-500">
                            {{ __('candidates::files.messages.upload_hint') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6 px-5 py-5">
            <section class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-[0_18px_40px_-30px_rgba(15,23,42,0.16)]">
                <div class="flex flex-col gap-4 border-b border-slate-100 pb-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm">
                            <x-dynamic-component :component="$draftIcon" size="w-5 h-5" color="text-white" hover="text-white" />
                        </div>
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-base font-semibold text-slate-900">{{ __('candidates::files.titles.new_file') }}</h4>
                                <span class="inline-flex items-center rounded-full border border-slate-200 {{ $draftTheme['badge'] }} px-2.5 py-1 text-[12px] font-semibold uppercase tracking-tight shadow-sm">
                                    {{ __('candidates::files.categories.'.($draft['category'] ?? 'other')) }}
                                </span>
                            </div>
                            <p class="max-w-3xl text-sm text-slate-500">{{ __('candidates::files.messages.private_storage_hint') }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 xl:grid-cols-[280px,minmax(0,1fr)] xl:items-start">
                    <div class="space-y-2">
                        <x-label for="uploadedFile">{{ __('candidates::files.labels.file') }}</x-label>
                        <div
                            x-data="{ isUploading: false, progress: 0 }"
                            x-on:livewire-upload-start="isUploading = true"
                            x-on:livewire-upload-finish="isUploading = false"
                            x-on:livewire-upload-error="isUploading = false"
                            x-on:livewire-upload-progress="progress = $event.detail.progress"
                            class="space-y-2"
                        >
                            <label class="group block cursor-pointer">
                                <input type="file" class="hidden" wire:model="uploadedFile" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg" />
                                <div class="flex min-h-[92px] items-center gap-4 rounded-[20px] border border-dashed border-slate-300 bg-slate-50 px-4 py-4 transition duration-200 group-hover:border-slate-400 group-hover:bg-slate-100/80">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                        @if ($uploadedPreviewUrl)
                                            <img src="{{ $uploadedPreviewUrl }}" alt="{{ __('candidates::files.labels.file') }}" class="h-full w-full object-cover" />
                                        @else
                                            <x-dynamic-component :component="$draftIcon" size="w-7 h-7" color="text-slate-500" hover="text-slate-600" />
                                        @endif
                                    </div>
                                    <div class="min-w-0 space-y-1">
                                        <div class="text-sm font-semibold text-slate-800">{{ __('candidates::files.messages.upload_hint') }}</div>
                                        <div class="text-xs leading-5 text-slate-500 break-all">{{ $uploadedFile ? $uploadedFile->getClientOriginalName() : __('candidates::files.messages.drag_drop_hint') }}</div>
                                    </div>
                                </div>
                            </label>
                            <div x-show="isUploading" class="overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-slate-900 transition-all duration-200" x-bind:style="`width:${progress}%`"></div>
                            </div>
                        </div>
                        @error('uploadedFile')
                            <x-validation>{{ $message }}</x-validation>
                        @enderror
                    </div>

                    <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr),220px]">
                        <div class="space-y-2">
                            <x-label for="draft.display_name">{{ __('candidates::files.labels.display_name') }}</x-label>
                            <x-livewire-input mode="gray" name="draft.display_name" wire:model.live="draft.display_name"></x-livewire-input>
                            @error('draft.display_name')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <x-ui.select-dropdown
                                :label="__('candidates::files.labels.category')"
                                placeholder="---"
                                mode="gray"
                                class="w-full"
                                wire:model.live="draft.category"
                                :model="$categoryOptions"
                            />
                            @error('draft.category')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </div>

                        <div class="space-y-2 xl:col-span-2">
                            <x-label for="draft.notes">{{ __('candidates::files.labels.notes') }}</x-label>
                            <x-textarea class="!min-h-[64px]" mode="gray" name="draft.notes" wire:model.live="draft.notes"></x-textarea>
                            @error('draft.notes')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </div>

                        <div class="xl:col-span-2 xl:flex xl:justify-end">
                            <x-button mode="black" class="w-full !rounded-2xl !py-3 xl:w-[220px]" wire:click="addFile">
                                {{ __('candidates::files.actions.add_file') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </section>

            <div class="rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_40px_-30px_rgba(15,23,42,0.12)]">
                <div class="flex gap-4 border-b border-slate-100 px-5 py-5 items-center justify-between">
                  <div class="w-full w-max">
                    <x-ui.select-dropdown
                        :label="__('candidates::files.labels.filter_category')"
                        :placeholder="__('candidates::files.labels.all_categories')"
                        mode="gray"
                        class="w-full"
                        wire:model.live="ui.category_filter"
                        :model="$categoryOptions"
                    />
                </div>
                  <div class="hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-tight text-slate-500 lg:flex lg:flex-none">
                    {{ count($visibleFiles) }} {{ __('candidates::files.labels.document') }}
                  </div>      
                </div>

                <div class="max-h-[calc(100vh-24rem)] overflow-y-auto px-5 pb-5 pt-4">
                    @if (count($visibleFiles))
                        <div class="space-y-3">
                            @foreach($visibleFiles as $key => $file)
                                @php
                                    $theme = $this->categoryTheme($file['category'] ?? 'other');
                                    $icon = $this->categoryIcon($file['category'] ?? 'other');
                                @endphp
                                <article class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-[0_14px_28px_-24px_rgba(15,23,42,0.14)] transition duration-200 hover:border-slate-300 hover:shadow-[0_18px_34px_-24px_rgba(15,23,42,0.22)] focus-within:border-slate-400 focus-within:shadow-[0_20px_36px_-24px_rgba(15,23,42,0.22)]">
                                    <div class="grid gap-4 px-4 py-4 xl:grid-cols-[50px,150px,minmax(0,1fr),150px,36px] xl:items-start">
                                        <div class="flex flex-col items-start gap-2">
                                            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-[1rem] border border-slate-200 bg-slate-50 shadow-sm">
                                                @if (!empty($file['is_previewable']) && !empty($file['preview_url']))
                                                    <img src="{{ $file['preview_url'] }}" alt="{{ $file['display_name'] }}" class="h-full w-full object-cover" />
                                                @else
                                                    <x-dynamic-component :component="$icon" size="w-5 h-5" color="text-slate-600" hover="text-slate-700" />
                                                @endif
                                            </div>
                                            <div class="rounded-full border border-slate-900 bg-black px-2.5 py-1 text-[11px] font-semibold font-mono uppercase tracking-tight text-slate-50 shadow-sm">
                                                {{ $file['extension'] }}
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <select
                                                wire:model.live="files.{{ $key }}.category"
                                                class="w-full rounded-full border border-slate-200 bg-white px-3 py-2 text-[12px] font-semibold uppercase tracking-tight text-slate-700 shadow-sm"
                                            >
                                                @foreach($categoryOptions as $option)
                                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <div class="space-y-1">
                                              <x-label for="files.{{ $key }}.display_name">{{ __('candidates::files.labels.display_name') }}</x-label>
                                              <x-livewire-input mode="gray" name="files.{{ $key }}.display_name" wire:model.live="files.{{ $key }}.display_name"></x-livewire-input>
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                          <x-label for="files.{{ $key }}.notes">{{ __('candidates::files.labels.notes') }}</x-label>
                                          <x-textarea class="!min-h-[56px]" mode="gray" name="files.{{ $key }}.notes" wire:model.live="files.{{ $key }}.notes"></x-textarea>
                                        </div>

                                        <div class="rounded-[20px] border border-slate-200 bg-slate-50 px-3 py-2 shadow-sm">
                                            <dl class="space-y-2 text-sm">
                                                <div class="space-y-1">
                                                    <dt class="text-[12px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::common.labels.created_date') }}</dt>
                                                    <dd class="break-words text-slate-700">{{ $file['created_at_label'] }}</dd>
                                                </div>
                                                <div class="space-y-1">
                                                    <dt class="text-[12px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::files.labels.size') }}</dt>
                                                    <dd class="text-slate-700">{{ \Illuminate\Support\Number::fileSize((int) $file['size_bytes']) }}</dd>
                                                </div>
                                            </dl>
                                        </div>

                                        <div class="flex flex-row justify-end gap-2 xl:flex-col xl:items-center">
                                            @if (!empty($file['download_url']))
                                                <a href="{{ $file['download_url'] }}"
                                                   class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 shadow-sm transition hover:border-slate-300 hover:text-slate-700"
                                                   title="{{ __('candidates::files.labels.open_file') }}">
                                                    <x-icons.arrow-icon size="w-4 h-4" color="text-slate-400" hover="text-slate-700"></x-icons.arrow-icon>
                                                </a>
                                            @endif

                                            <button
                                                x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('candidates::files.messages.remove_confirm')), confirmText: @js(__('ui::common.actions.delete')), run: () => $wire.removeFile({{ $key }}) })"
                                                class="flex h-10 w-10 items-center justify-center rounded-full border border-rose-100 bg-rose-50 text-rose-400 shadow-sm transition hover:border-rose-200 hover:text-rose-600"
                                            >
                                                <x-icons.delete-icon size="w-4 h-4" color="text-rose-400" hover="text-rose-600"></x-icons.delete-icon>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="flex min-h-[260px] items-center justify-center rounded-[26px] border border-dashed border-slate-200 bg-slate-50/80 px-6 py-10 text-center">
                            <div class="flex max-w-sm flex-col items-center space-y-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                    <x-icons.document-icon size="w-8 h-8" color="text-slate-400"></x-icons.document-icon>
                                </div>
                                <div class="space-y-2">
                                    <h4 class="text-lg font-semibold text-slate-700">{{ __('candidates::files.messages.no_files_for_filter') }}</h4>
                                    <p class="text-sm text-slate-500">{{ __('candidates::files.messages.upload_hint') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="sticky bottom-0 -mx-5 border-t border-slate-200 bg-white/95 px-5 pb-1 pt-4 backdrop-blur">
                <div class="flex items-center justify-end">
                    <x-modal-button>{{ __('candidates::common.labels.save') }}</x-modal-button>
                </div>
            </div>
        </div>
    </section>
</div>
