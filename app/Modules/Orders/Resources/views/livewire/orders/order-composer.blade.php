<div>
    {{-- Modal header --}}
    <div class="sidemenu-title">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
                {{ $this->isEditing() ? __('orders::order_composer.edit_title') : __('orders::order_composer.create_title') }}
            </h2>
            @if ($this->isEditing())
                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                    {{ $orderNumber }}
                </span>
            @endif
        </div>
        <p class="mt-1 text-sm text-gray-400">{{ __('orders::order_composer.labels.edit_hint') }}</p>
    </div>

    <div class="flex flex-col w-full px-0 py-6 mx-auto space-y-6 bg-white">

        @php
            $inputClass = 'block w-full mt-1 text-sm transition duration-100 ease-in-out border-none rounded-lg shadow-sm bg-neutral-100 focus:ring-blue-500 focus:border-blue-500 px-3 py-2';
        @endphp

        {{-- Step 1: type + employee --}}
        <section class="space-y-4 rounded-2xl border border-zinc-200 bg-zinc-50/60 p-5">
            <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                <span class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-full bg-zinc-900">1</span>
                {{ __('orders::order_composer.labels.step_basics') }}
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="presetCode">{{ __('orders::order_composer.labels.type') }}</x-label>
                    <select wire:model.live="presetCode" id="presetCode" {{ $this->isEditing() ? 'disabled' : '' }}
                        class="{{ $inputClass }} {{ $this->isEditing() ? 'opacity-60' : '' }}">
                        <option value="">—</option>
                        @foreach ($this->presets as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('presetCode') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                {{-- Employee picker --}}
                <div class="relative">
                    <x-label for="personnelQuery">{{ __('orders::order_composer.labels.employee') }}</x-label>
                    @if ($personnelLabel)
                        <div class="flex items-center justify-between px-3 py-2 mt-1 text-sm rounded-lg bg-neutral-100">
                            <span class="font-medium text-zinc-900">{{ $personnelLabel }}</span>
                            <button type="button" wire:click="clearPersonnel" class="text-zinc-400 hover:text-red-600">✕</button>
                        </div>
                    @else
                        <x-livewire-input mode="gray" name="personnelQuery"
                            wire:model.live.debounce.300ms="personnelQuery"
                            placeholder="{{ __('orders::order_composer.labels.employee_search') }}" />
                        @if (count($this->personnelResults) > 0)
                            <div class="absolute z-20 w-full mt-1 overflow-auto bg-white border shadow-lg max-h-56 rounded-lg border-zinc-200">
                                @foreach ($this->personnelResults as $r)
                                    <button type="button" wire:click="selectPersonnel({{ $r['id'] }})"
                                        class="block w-full px-3 py-2 text-sm text-left hover:bg-zinc-100">{{ $r['label'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    @endif
                    @error('personnelId') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                <div>
                    <x-label for="orderNumber">{{ __('orders::order_composer.labels.number') }}</x-label>
                    <x-livewire-input mode="gray" name="orderNumber" wire:model="orderNumber" />
                    @error('orderNumber') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-label for="orderDate">{{ __('orders::order_composer.labels.date') }}</x-label>
                    <x-livewire-input mode="gray" name="orderDate" wire:model="orderDate" />
                </div>
            </div>
        </section>

        {{-- Step 2: details --}}
        @if (count($this->fieldDefs))
            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-zinc-50/60 p-5">
                <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                    <span class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-full bg-zinc-900">2</span>
                    {{ __('orders::order_composer.labels.step_details') }}
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($this->fieldDefs as $field)
                        <div>
                            <x-label for="fields.{{ $field['key'] }}">{{ $field['label'] }}</x-label>
                            @if ($field['type'] === 'structure' || $field['type'] === 'position')
                                <select wire:model="fields.{{ $field['key'] }}" class="{{ $inputClass }}">
                                    <option value="">—</option>
                                    @foreach (($field['type'] === 'structure' ? $this->structureOptions : $this->positionOptions) as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <x-livewire-input mode="gray" type="{{ $field['type'] }}"
                                    name="fields.{{ $field['key'] }}" wire:model="fields.{{ $field['key'] }}" />
                            @endif
                        </div>
                    @endforeach
                </div>
                <div>
                    <x-button mode="black" wire:click="generatePreview" wire:loading.attr="disabled" wire:target="generatePreview">
                        <span wire:loading.remove wire:target="generatePreview">{{ __('orders::order_composer.actions.generate') }}</span>
                        <span wire:loading wire:target="generatePreview">…</span>
                    </x-button>
                </div>
            </section>
        @endif

        {{-- Step 3: preview + inline edit --}}
        @if ($previewHtml !== '')
            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                    <span class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-full bg-zinc-900">3</span>
                    {{ __('orders::order_composer.labels.step_preview') }}
                </div>

                <div
                    x-data="{
                        sync() { $wire.set('editedHtml', $root.innerHTML, false); $wire.set('manuallyEdited', true, false) },
                        seed(html) { if (document.activeElement !== $root) { $root.innerHTML = html } },
                    }"
                    x-init="seed($wire.previewHtml)"
                    x-effect="seed($wire.previewHtml)"
                    @input="sync()"
                    contenteditable="true"
                    class="mx-auto min-h-[360px] max-w-3xl rounded-lg border border-zinc-200 bg-white p-8 text-[15px] leading-7 text-zinc-900 shadow-inner focus:outline-none focus:ring-2 focus:ring-zinc-900">
                </div>
                @error('previewHtml') <x-validation>{{ $message }}</x-validation> @enderror

                <div class="flex justify-end pt-2">
                    <x-button mode="success" wire:click="issue" wire:loading.attr="disabled" wire:target="issue">
                        {{ $this->isEditing() ? __('orders::order_composer.actions.save') : __('orders::order_composer.actions.issue') }}
                    </x-button>
                </div>
            </section>
        @endif

        {{-- Replace generated Word with a corrected upload (edit mode only) --}}
        @if ($this->isEditing())
            <section class="space-y-3 rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/60 p-5">
                <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                    <x-icons.print-file color="text-zinc-500" hover="text-zinc-600"></x-icons.print-file>
                    {{ __('orders::order_composer.labels.replace_word') }}
                </div>
                <p class="text-xs leading-5 text-zinc-500">{{ __('orders::order_composer.labels.replace_word_hint') }}</p>

                @if ($hasUploadedDocx)
                    <div class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                        ✓ {{ __('orders::order_composer.labels.uploaded_word_exists') }}
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    <input type="file" wire:model="uploadedDocx" accept=".docx,.doc"
                        class="text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-700">
                    <x-button mode="black" wire:click="uploadDocx" wire:loading.attr="disabled" wire:target="uploadDocx,uploadedDocx"
                        @disabled(! $uploadedDocx)>
                        <span wire:loading.remove wire:target="uploadDocx,uploadedDocx">{{ __('orders::order_composer.actions.upload_replace') }}</span>
                        <span wire:loading wire:target="uploadDocx,uploadedDocx">…</span>
                    </x-button>
                </div>
                @error('uploadedDocx') <x-validation>{{ $message }}</x-validation> @enderror
            </section>
        @endif
    </div>
</div>
