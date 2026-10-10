{{-- «İştirakçılar»: the employees of a multi-participant order and their own values.
     Expects: $participantFieldDefs, $lookupOptions. --}}
<section class="space-y-4 rounded-2xl border border-zinc-200 bg-zinc-50/60 p-5">
    <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
        <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        {{ __('orders::order_composer.participants.title') }}
        @if (count($participantIds))
            <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium text-zinc-500">{{ count($participantIds) }}</span>
        @endif
    </div>
    <p class="text-xs leading-5 text-zinc-500">{{ __('orders::order_composer.participants.hint') }}</p>

    <div class="sm:max-w-md">
        @include('orders::livewire.orders.partials.order-composer.subject-picker', [
            'query' => 'personnelQuery',
            'selected' => null,
            'results' => $this->personnelResults,
            'select' => 'addParticipant',
            'clear' => 'clearPersonnel',
            'error' => 'participants',
            'label' => __('orders::order_composer.participants.add'),
            'placeholder' => __('orders::order_composer.labels.employee_search'),
        ])
    </div>

    @if (count($participantIds) === 0)
        <div class="rounded-xl border border-dashed border-zinc-200 py-6 text-center text-[13px] text-zinc-400">
            {{ __('orders::order_composer.participants.empty') }}
        </div>
    @else
        <div class="space-y-2">
            @foreach ($participantIds as $index => $participantId)
                <div wire:key="participant-{{ $participantId }}" @class([
                    'rounded-xl border bg-white p-3',
                    'border-rose-200' => $errors->has('participants.'.$index),
                    'border-zinc-200' => ! $errors->has('participants.'.$index),
                ])>
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-zinc-900/90 text-[11px] font-mono text-white">{{ $index + 1 }}</span>
                        <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-zinc-900">{{ $participantLabels[$participantId] ?? '#'.$participantId }}</span>
                        <button type="button" wire:click="removeParticipant({{ $participantId }})"
                            title="{{ __('orders::order_composer.participants.remove') }}" aria-label="{{ __('orders::order_composer.participants.remove') }}"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-rose-50 hover:text-rose-600">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    @if (count($participantFieldDefs))
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            @foreach ($participantFieldDefs as $field)
                                @php
                                    $isOverride = $field['scope'] === \App\Models\OrderWordTemplate::SCOPE_OVERRIDE;
                                    $model = 'participantFields.'.$participantId.'.'.$field['key'];
                                @endphp
                                <div>
                                    <x-label for="{{ $model }}">
                                        {{ $field['label'] }}
                                        @if ($isOverride)
                                            <span class="font-normal text-zinc-400">({{ __('orders::order_composer.participants.override') }})</span>
                                        @elseif (! ($field['required'] ?? true))
                                            <span class="font-normal text-zinc-400">({{ __('orders::order_composer.labels.optional') }})</span>
                                        @endif
                                    </x-label>
                                    @if (isset($lookupOptions[$field['type']]))
                                        <x-orders.lookup-picker wire:model="{{ $model }}" :options="$lookupOptions[$field['type']]" />
                                    @else
                                        @php
                                            $htmlType = in_array($field['type'], ['number', 'number_words']) ? 'number'
                                                : (in_array($field['type'], ['date', 'work_year']) ? 'date' : 'text');
                                        @endphp
                                        <x-livewire-input mode="gray" type="{{ $htmlType }}" name="{{ $model }}" wire:model.live.debounce.400ms="{{ $model }}" />
                                    @endif
                                    @error('participants.'.$index.'.fields.'.$field['key'])
                                        <x-validation>{{ $message }}</x-validation>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('participants.'.$index)
                        <p class="mt-2 flex items-center gap-1.5 text-[12px] font-medium text-rose-600">
                            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @endforeach
        </div>
    @endif
</section>
