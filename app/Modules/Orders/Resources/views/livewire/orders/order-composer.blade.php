<div>
    {{-- Staff-schedule vacancy prompt: when a hire has no free slot, the server fires
         `order-vacancy-missing` and we surface the global confirm modal with an action
         that creates the slot and continues issuing — no page reload, no rebuild. --}}
    <div x-data
         @order-vacancy-missing.window="$dispatch('confirm-action', {
            title: @js(__('orders::order_composer.vacancy.title')),
            message: $event.detail.message,
            confirmText: @js(__('orders::order_composer.vacancy.create')),
            tone: 'amber',
            run: () => $wire.createVacancyAndIssue(),
         })"></div>

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

                @if ($this->isHire())
                    {{-- Candidate picker (hire orders) — only "ready for order" candidates --}}
                    <div class="relative">
                        <x-label for="candidateQuery">{{ __('orders::order_composer.labels.candidate') }}</x-label>
                        @if ($candidateLabel)
                            <div class="flex items-center justify-between px-3 py-2 mt-1 text-sm rounded-lg bg-neutral-100">
                                <span class="font-medium text-zinc-900">{{ $candidateLabel }}</span>
                                <button type="button" wire:click="clearCandidate" class="text-zinc-400 hover:text-red-600">✕</button>
                            </div>
                        @else
                            <x-livewire-input mode="gray" name="candidateQuery"
                                wire:model.live.debounce.300ms="candidateQuery"
                                placeholder="{{ __('orders::order_composer.labels.candidate_search') }}" />
                            @if (count($this->candidateResults) > 0)
                                <div class="absolute z-20 w-full mt-1 overflow-auto bg-white border shadow-lg max-h-56 rounded-lg border-zinc-200">
                                    @foreach ($this->candidateResults as $r)
                                        <button type="button" wire:click="selectCandidate({{ $r['id'] }})"
                                            class="block w-full px-3 py-2 text-sm text-left hover:bg-zinc-100">{{ $r['label'] }}</button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                        @error('candidateId') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    <div>
                        <x-label for="hireStructureId">{{ __('orders::order_composer.labels.hire_structure') }}</x-label>
                        <x-orders.lookup-picker wire:model="hireStructureId" :options="$this->lookupOptions['structure']" />
                    </div>
                    <div>
                        <x-label for="hirePositionId">{{ __('orders::order_composer.labels.hire_position') }}</x-label>
                        <x-orders.lookup-picker wire:model="hirePositionId" :options="$this->lookupOptions['position']" />
                        @error('hirePositionId') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @else
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
                @endif

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

        {{-- Vacation balance: entitled / used / remaining for the selected employee --}}
        @if ($this->vacationBalance)
            @php $vb = $this->vacationBalance; $over = $vb['requested'] > 0 && $vb['requested'] > $vb['remaining']; @endphp
            <section @class([
                'rounded-2xl border p-5 transition-colors',
                'border-rose-200 bg-rose-50/50' => $over,
                'border-zinc-200 bg-white' => ! $over,
            ])>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                        <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        {{ __('orders::order_composer.vacation.balance') }}
                        <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium text-zinc-500 tabular-nums">{{ $vb['year'] }}</span>
                    </div>
                    @if ($vb['requested'] > 0)
                        <span @class([
                            'inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[12px] font-semibold tabular-nums ring-1 ring-inset',
                            'bg-rose-50 text-rose-700 ring-rose-100' => $over,
                            'bg-emerald-50 text-emerald-700 ring-emerald-100' => ! $over,
                        ])>{{ __('orders::order_composer.labels.fields') }}: {{ $vb['requested'] }} {{ __('orders::order_composer.vacation.days_suffix') }}</span>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-zinc-50 px-3 py-2.5 ring-1 ring-inset ring-zinc-200/70">
                        <p class="text-[11px] font-medium text-zinc-400">{{ __('orders::order_composer.vacation.total') }}</p>
                        <p class="mt-0.5 text-xl font-semibold tracking-tight text-zinc-900 tabular-nums">{{ $vb['total'] }}</p>
                    </div>
                    <div class="rounded-xl bg-zinc-50 px-3 py-2.5 ring-1 ring-inset ring-zinc-200/70">
                        <p class="text-[11px] font-medium text-zinc-400">{{ __('orders::order_composer.vacation.used') }}</p>
                        <p class="mt-0.5 text-xl font-semibold tracking-tight text-zinc-700 tabular-nums">{{ $vb['used'] }}</p>
                    </div>
                    <div @class([
                        'rounded-xl px-3 py-2.5 ring-1 ring-inset',
                        'bg-rose-50 ring-rose-100' => $vb['remaining'] <= 0 || $over,
                        'bg-emerald-50 ring-emerald-100' => $vb['remaining'] > 0 && ! $over,
                    ])>
                        <p class="text-[11px] font-medium text-zinc-400">{{ __('orders::order_composer.vacation.remaining') }}</p>
                        <p @class([
                            'mt-0.5 text-xl font-semibold tracking-tight tabular-nums',
                            'text-rose-700' => $vb['remaining'] <= 0 || $over,
                            'text-emerald-700' => $vb['remaining'] > 0 && ! $over,
                        ])>{{ $vb['remaining'] }}</p>
                    </div>
                </div>

                @if ($over)
                    <p class="mt-3 flex items-center gap-1.5 text-[12px] font-medium text-rose-600">
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        {{ __('orders::order_composer.vacation.exceeded', ['year' => $vb['year'], 'total' => $vb['total'], 'used' => $vb['used'], 'remaining' => $vb['remaining'], 'requested' => $vb['requested']]) }}
                    </p>
                @endif
            </section>
        @endif

        {{-- Step 2: manual fields the template declared --}}
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
                            @if (isset($this->lookupOptions[$field['type']]))
                                <x-orders.lookup-picker wire:model="fields.{{ $field['key'] }}"
                                    :options="$this->lookupOptions[$field['type']]" />
                            @else
                                @php
                                    $htmlType = in_array($field['type'], ['number', 'number_words']) ? 'number'
                                        : (in_array($field['type'], ['date', 'work_year']) ? 'date' : 'text');
                                @endphp
                                <x-livewire-input mode="gray" type="{{ $htmlType }}"
                                    name="fields.{{ $field['key'] }}" wire:model.live.debounce.400ms="fields.{{ $field['key'] }}" />
                                @if ($field['type'] === 'work_year')
                                    <p class="mt-1 text-[11px] text-zinc-400">{{ __('orders::order_composer.labels.work_year_hint') }}</p>
                                @endif
                            @endif
                            @error('fields.'.$field['key'])
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Step 3: generate --}}
        @if ($presetCode !== '')
            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                    <span class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-full bg-zinc-900">3</span>
                    {{ __('orders::order_composer.labels.step_preview') }}
                </div>
                <p class="text-xs leading-5 text-zinc-500">{{ __('orders::order_composer.labels.docx_generate_hint') }}</p>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-1">
                    <x-button mode="secondary" wire:click="preview" wire:loading.attr="disabled" wire:target="preview">
                        <span wire:loading.remove wire:target="preview">{{ __('orders::order_composer.actions.preview_word') }}</span>
                        <span wire:loading wire:target="preview">…</span>
                    </x-button>
                    <x-button mode="default" wire:click="downloadWord" wire:loading.attr="disabled" wire:target="downloadWord">
                        <span wire:loading.remove wire:target="downloadWord">{{ __('orders::order_composer.actions.download_word') }}</span>
                        <span wire:loading wire:target="downloadWord">…</span>
                    </x-button>
                    <x-button mode="success" wire:click="issue" wire:loading.attr="disabled" wire:target="issue">
                        <span wire:loading.remove wire:target="issue">{{ $this->isEditing() ? __('orders::order_composer.actions.save') : __('orders::order_composer.actions.issue') }}</span>
                        <span wire:loading wire:target="issue">…</span>
                    </x-button>
                </div>
                @error('previewPdf') <x-validation>{{ $message }}</x-validation> @enderror

                {{-- Faithful inline PDF preview of the generated document --}}
                <div wire:loading.flex wire:target="preview" class="items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 py-10 text-sm text-zinc-400">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
                    {{ __('orders::order_composer.actions.preview_word') }}…
                </div>
                @if ($previewPdf !== '')
                    <div wire:loading.remove wire:target="preview" class="overflow-hidden rounded-xl border border-zinc-200 shadow-inner">
                        <iframe src="data:application/pdf;base64,{{ $previewPdf }}" class="h-[70vh] w-full" title="preview"></iframe>
                    </div>
                @endif
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
