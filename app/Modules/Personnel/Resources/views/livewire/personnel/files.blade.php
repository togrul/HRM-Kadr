<div class="flex flex-col space-y-5">
    <div class="sidemenu-title">
        <h2 class="font-title text-xl font-semibold text-gray-500" id="slide-over-title">
            {{ $title ?? '' }}
        </h2>
    </div>

    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_60px_-36px_rgba(15,23,42,0.35)]">
        <div class="border-b border-slate-200 bg-gradient-to-br from-slate-50 via-white to-slate-100 px-5 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm">
                        <x-icons.files-icon size="w-8 h-8" color="text-white"></x-icons.files-icon>
                    </div>
                    <div class="space-y-2">
                        <div class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white">
                            {{ __('personnel::files.labels.document') }}
                        </div>
                        <h3 class="text-2xl font-semibold text-slate-900">{{ __('personnel::files.titles.all_documents') }}</h3>
                        <p class="max-w-3xl text-sm text-slate-500">
                            {{ __('personnel::files.messages.upload_hint') }}
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500 shadow-sm">
                    {{ count($file_list) }} {{ __('personnel::files.labels.document') }}
                </div>
            </div>
        </div>

        <div class="space-y-6 px-5 py-5">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[320px,1fr]">
                <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <h4 class="text-base font-semibold text-slate-900">{{ __('personnel::files.titles.new_file') }}</h4>
                            <p class="text-sm text-slate-500">{{ __('personnel::files.messages.upload_hint') }}</p>
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <x-ui.file-upload
                                    model="uploadedFile"
                                    :data="$uploadedFile"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg"
                                />
                                @error('uploadedFile')
                                    <x-validation>{{ $message }}</x-validation>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <x-label for="files.filename">{{ __('personnel::files.labels.file_name') }}</x-label>
                                <x-livewire-input mode="gray" name="files.filename" wire:model.live="files.filename"></x-livewire-input>
                                @error('files.filename')
                                    <x-validation>{{ $message }}</x-validation>
                                @enderror
                            </div>

                            <x-button mode="black" class="w-full !rounded-2xl !py-3" wire:click="addFile">
                                {{ __('personnel::common.actions.add') }}
                            </x-button>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    @if (count($file_list))
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach($file_list as $key => $file)
                                @php
                                    $route = $this->fileRoute($file);
                                    $extension = $this->fileExtension($file);
                                    $sizeLabel = $this->fileSizeLabel($file);
                                    $iconTone = match ($extension) {
                                        'PDF' => 'text-slate-300',
                                        'DOC', 'DOCX' => 'text-slate-300',
                                        'XLS', 'XLSX', 'CSV' => 'text-slate-300',
                                        'PSD', 'AI' => 'text-slate-300',
                                        default => 'text-slate-300',
                                    };
                                @endphp

                                <article class="group relative overflow-hidden rounded-[34px] bg-[#f7f8fb] p-5 shadow-[0_18px_40px_-30px_rgba(15,23,42,0.18)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_24px_56px_-30px_rgba(15,23,42,0.22)]">
                                    <div class="absolute right-4 top-4 flex flex-col gap-2">
                                        <a href="{{ $route }}"
                                            target="_blank"
                                            rel="noreferrer"
                                            class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-300 shadow-[0_12px_28px_-18px_rgba(15,23,42,0.22)] transition hover:text-slate-600"
                                            title="{{ __('personnel::files.labels.open_file') }}">
                                            <x-icons.arrow-icon size="w-5 h-5" color="text-slate-300" hover="text-slate-600"></x-icons.arrow-icon>
                                        </a>

                                        <button
                                            onclick="confirm('{{ __('personnel::common.messages.remove_data_confirm') }}') || event.stopImmediatePropagation()"
                                            wire:click="deleteFile({{ $key }})"
                                            class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-rose-300 shadow-[0_12px_28px_-18px_rgba(15,23,42,0.22)] transition hover:text-rose-500"
                                        >
                                            <x-icons.delete-icon size="w-5 h-5" color="text-rose-300" hover="text-rose-500"></x-icons.delete-icon>
                                        </button>
                                    </div>

                                    <div class="flex min-h-[250px] flex-col rounded-[30px] bg-white/55 px-6 py-6 pr-20">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-[18px] bg-white shadow-[0_12px_24px_-20px_rgba(15,23,42,0.25)]">
                                            <x-icons.document-icon size="w-9 h-9" color="{{ $iconTone }}" hover="{{ $iconTone }}"></x-icons.document-icon>
                                        </div>

                                        <div class="mt-auto space-y-2 pt-12">
                                            <a target="_blank" rel="noreferrer" href="{{ $route }}"
                                                class="line-clamp-3 break-words text-[20px] font-medium leading-[1.28] tracking-[-0.03em] text-slate-500 transition hover:text-slate-700">
                                                {{ $file['filename'] }}
                                            </a>
                                            <p class="text-base font-medium text-slate-300">{{ $sizeLabel }}</p>
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
                                    <h4 class="text-lg font-semibold text-slate-700">{{ __('personnel::files.messages.no_files_added') }}</h4>
                                    <p class="text-sm text-slate-500">{{ __('personnel::files.messages.upload_hint') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-end justify-between w-full">
                <x-modal-button>{{ __('personnel::common.actions.save') }}</x-modal-button>
            </div>
        </div>
    </section>
</div>
