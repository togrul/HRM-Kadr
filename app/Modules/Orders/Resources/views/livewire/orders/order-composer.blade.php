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

    @php
        $isEditing = $this->isEditing();
        $inputClass = 'block w-full mt-1 text-sm transition duration-100 ease-in-out border-none rounded-lg shadow-sm bg-neutral-100 focus:ring-blue-500 focus:border-blue-500 px-3 py-2';
    @endphp

    {{-- Modal header --}}
    <div class="sidemenu-title">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
                {{ $isEditing ? __('orders::order_composer.edit_title') : __('orders::order_composer.create_title') }}
            </h2>
            @if ($isEditing)
                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                    {{ $orderNumber }}
                </span>
            @endif
        </div>
        <p class="mt-1 text-sm text-gray-400">{{ __('orders::order_composer.labels.edit_hint') }}</p>
    </div>

    <div class="flex flex-col w-full px-0 py-6 mx-auto space-y-6 bg-white">

        {{-- Step 1: type + subject --}}
        <section class="space-y-4 rounded-2xl border border-zinc-200 bg-zinc-50/60 p-5">
            <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                <span class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-full bg-zinc-900">1</span>
                {{ __('orders::order_composer.labels.step_basics') }}
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="presetCode">{{ __('orders::order_composer.labels.type') }}</x-label>
                    <x-ui.select class="mt-1 {{ $isEditing ? 'opacity-60' : '' }}" wire:model.live="presetCode" id="presetCode" :disabled="$isEditing">
                        <option value="">—</option>
                        @foreach ($this->presets as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                    @error('presetCode') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                @if ($this->isHire())
                    {{-- Candidate picker (hire orders) — only "ready for order" candidates --}}
                    @include('orders::livewire.orders.partials.order-composer.subject-picker', [
                        'query' => 'candidateQuery',
                        'selected' => $candidateLabel,
                        'results' => $candidateLabel ? [] : $this->candidateResults,
                        'select' => 'selectCandidate',
                        'clear' => 'clearCandidate',
                        'error' => 'candidateId',
                        'label' => __('orders::order_composer.labels.candidate'),
                        'placeholder' => __('orders::order_composer.labels.candidate_search'),
                    ])

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
                    @include('orders::livewire.orders.partials.order-composer.subject-picker', [
                        'query' => 'personnelQuery',
                        'selected' => $personnelLabel,
                        'results' => $personnelLabel ? [] : $this->personnelResults,
                        'select' => 'selectPersonnel',
                        'clear' => 'clearPersonnel',
                        'error' => 'personnelId',
                        'label' => __('orders::order_composer.labels.employee'),
                        'placeholder' => __('orders::order_composer.labels.employee_search'),
                    ])
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

        @if ($this->vacationBalance)
            @include('orders::livewire.orders.partials.order-composer.vacation-balance', ['vb' => $this->vacationBalance])
        @endif

        @if (count($this->fieldDefs))
            @include('orders::livewire.orders.partials.order-composer.fields', [
                'fieldDefs' => $this->fieldDefs,
                'lookupOptions' => $this->lookupOptions,
            ])
        @endif

        @if ($presetCode !== '')
            @include('orders::livewire.orders.partials.order-composer.generate')
        @endif

        @if ($isEditing)
            @include('orders::livewire.orders.partials.order-composer.replace-word')
        @endif
    </div>
</div>
