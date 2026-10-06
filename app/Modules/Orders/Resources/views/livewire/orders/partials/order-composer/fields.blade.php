{{-- Step 2: the manual fields the selected template declared. Expects: $fieldDefs, $lookupOptions. --}}
<section class="space-y-4 rounded-2xl border border-zinc-200 bg-zinc-50/60 p-5">
    <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
        <span class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-full bg-zinc-900">2</span>
        {{ __('orders::order_composer.labels.step_details') }}
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($fieldDefs as $field)
            <div>
                <x-label for="fields.{{ $field['key'] }}">{{ $field['label'] }}</x-label>
                @if (isset($lookupOptions[$field['type']]))
                    <x-orders.lookup-picker wire:model="fields.{{ $field['key'] }}"
                        :options="$lookupOptions[$field['type']]" />
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
