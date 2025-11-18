@props([
    'model',
    'listData' => 'componentForms',
    'field' => "",
    'key' => 0,
    'isCoded' => false,
    'selectedId' => null,
])

@php
    $wordSuffixService = new \App\Services\WordSuffixService();
    $currentSelection = $selectedId;
    if ($currentSelection === null) {
        $rawValue = data_get($this->{$listData}[$key] ?? [], $field);
        if (method_exists($this, 'componentFieldValue')) {
            $currentSelection = $this->componentFieldValue($key, $field);
        } else {
            $currentSelection = is_array($rawValue) ? ($rawValue['id'] ?? null) : $rawValue;
        }
    }
@endphp

<li x-data="{openSubStructure: false}" class="py-1">
    <div class="flex justify-between w-full items-center">
        <div class="flex justify-start items-center space-x-2">
            @if(count($model->subs) > 0)
            {{-- arrow animasiyali deyisen--}}
            <button @click="openSubStructure = !openSubStructure" class="appearance-none flex justify-center items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500">
                    <path x-show="openSubStructure"
                          stroke-linecap="round"
                          stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                          x-transition:enter="transition ease-out duration-300"
                          x-transition:enter-start="opacity-0 scale-90"
                          x-transition:enter-end="opacity-100 scale-100"
                          x-transition:leave="transition ease-in duration-200"
                          x-transition:leave-start="opacity-100 scale-100"
                          x-transition:leave-end="opacity-0 scale-90"
                    />
                    <path
                        x-show="!openSubStructure"
                        stroke-linecap="round"
                        stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                    />
                </svg>
            </button>
            @else
                <span class="w-4 h-4"></span>
            @endif
            {{-- bolmenin adi --}}
            <span class="text-sm font-medium">{{ $slot }}</span>
        </div>
        {{-- button checkbox secmek ucun hansini --}}
        <button
            wire:click.prevent="setStructure({{ $model->id }},'{{ $listData }}','{{ $field }}',{{ $key }},{{ $isCoded ? 1 : 0 }})"
            @class([
                'appearance-none rounded-full w-6 h-6 border p-[3px] flex justify-center items-center transition-all duration-300',
                'border-gray-300 bg-white' => $model->id != $currentSelection,
                'border-green-500 bg-green-200' => $model->id == $currentSelection
            ])
        >
              <span @class([
                    'w-full h-full rounded-full transition-all duration-300',
                    'bg-white border-gray-300' => $model->id != $currentSelection,
                    'bg-green-500 border-green-500' => $model->id ==  $currentSelection
              ])></span>
        </button>
    </div>

    @if(count($model->subs) > 0)
        <ul class="pl-2 pt-1 rounded-lg flex-col flex shadow-sm bg-white"
            x-show="openSubStructure"
            x-transition:enter="transition ease-in-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-y-0 -translate-y-1/2"
            x-transition:enter-end="opacity-100 transform scale-y-100 translate-x-0"
            x-transition:leave="transition ease-in-out duration-300"
            x-transition:leave-start="opacity-100 transform scale-y-100 translate-y-0"
            x-transition:leave-end="opacity-0 transform scale-y-0 -translate-y-1/2"
        >
            @foreach ($model->subs as $sub)
                @php
                    $_level_name = strtolower((collect(\App\Enums\StructureEnum::cases())->pluck('name','value')[$sub->level]));
                    $_select_value = ($field == 'structure_id' && $isCoded)
                                   ? $sub->code."{$wordSuffixService->getNumberSuffix($sub->code)} {$_level_name}"
                                   : $sub->name;
                    $isCoded = $isCoded ?: 0;
                @endphp
                <x-radio-tree.item :$isCoded :$listData :$field :model="$sub" :$key :selected-id="$currentSelection">
                    - {{ $_select_value }}
                </x-radio-tree.item>
            @endforeach
        </ul>
    @endif
</li>
