@foreach ($this->candidatePackFieldRows() as $row)
    @php
        $columns = max(1, count($row));
        $gridClass = match ($columns) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-2',
            3 => 'grid-cols-3',
            default => 'grid-cols-4',
        };
    @endphp

    <div class="grid {{ $gridClass }} gap-2">
        @foreach ($row as $field)
            @php
                $showWhen = $field['show_when'] ?? null;
                $shouldRender = true;

                if (is_array($showWhen)) {
                    $shouldRender = data_get($candidate, $showWhen['field']) == ($showWhen['equals'] ?? null);
                }

                $fieldKey = $field['key'];
                $fieldLabel = __('candidates::common.labels.'.$fieldKey);
                $fieldType = $field['type'] ?? 'text';
                $colSpan = match ((int) ($field['cols'] ?? 1)) {
                    2 => 'col-span-2',
                    3 => 'col-span-3',
                    4 => 'col-span-4',
                    default => '',
                };
            @endphp

            @if ($shouldRender)
                <div class="flex flex-col {{ $colSpan }}">
                    @if ($fieldType === 'date')
                        <x-label for="candidate.{{ $fieldKey }}">{{ $fieldLabel }}</x-label>
                        <x-pikaday-input mode="gray" name="candidate.{{ $fieldKey }}" format="Y-MM-DD" wire:model.live="candidate.{{ $fieldKey }}">
                            <x-slot name="script">
                                $el.onchange = function () { @this.set('candidate.{{ $fieldKey }}', $el.value); }
                            </x-slot>
                        </x-pikaday-input>
                    @elseif ($fieldType === 'textarea')
                        <x-label for="candidate.{{ $fieldKey }}">{{ $fieldLabel }}</x-label>
                        <x-textarea mode="gray" name="candidate.{{ $fieldKey }}" wire:model="candidate.{{ $fieldKey }}"></x-textarea>
                    @elseif ($fieldType === 'radio')
                        <x-label for="candidate.{{ $fieldKey }}">{{ $fieldLabel }}</x-label>
                        <div class="flex flex-row flex-wrap gap-2">
                            @foreach ($this->candidateFieldOptions($field) as $option)
                                <label class="inline-flex items-center rounded bg-gray-100 px-2 py-2 shadow-sm">
                                    <input type="radio" class="form-radio" name="candidate.{{ $fieldKey }}" wire:model.live="candidate.{{ $fieldKey }}" value="{{ $option }}">
                                    <span class="ml-2 text-sm font-normal">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <x-label for="candidate.{{ $fieldKey }}">{{ $fieldLabel }}</x-label>
                        <x-livewire-input mode="gray" type="{{ $fieldType === 'number' ? 'number' : 'text' }}" name="candidate.{{ $fieldKey }}" wire:model="candidate.{{ $fieldKey }}"></x-livewire-input>
                    @endif

                    @error('candidate.'.$fieldKey) <x-validation>{{ $message }}</x-validation> @enderror
                </div>
            @endif
        @endforeach
    </div>
@endforeach
