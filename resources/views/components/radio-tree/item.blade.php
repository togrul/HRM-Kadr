@props([
    'model',
    'listData' => 'componentForms',
    'field' => "",
    'key' => 0,
    'isCoded' => false,
    'selectedId' => null,
    'suffixService' => null,
    'structureLevels' => null,
    'selectedResolver' => null,
])

@php
    $suffixService = $suffixService ?? app(\App\Services\WordSuffixService::class);
    $structureLevels = $structureLevels ?? collect(\App\Enums\StructureEnum::cases())->mapWithKeys(fn($c) => [$c->value => strtolower($c->name)]);
    $resolveSelected = $selectedResolver ?? function ($component, $list, $row, $field, $preset = null) {
        if ($preset !== null) {
            return $preset;
        }
        $rawValue = data_get($component->{$list}[$row] ?? [], $field);
        if (method_exists($component, 'componentFieldValue')) {
            return $component->componentFieldValue($row, $field);
        }
        return is_array($rawValue) ? ($rawValue['id'] ?? null) : $rawValue;
    };
    $currentSelection = $resolveSelected($this, $listData, $key, $field, $selectedId);
@endphp

<li x-data="{
        openSubStructure: openNodes[{{ $model->id }}] ?? false,
        toggle() {
            this.openSubStructure = !this.openSubStructure;
            openNodes[{{ $model->id }}] = this.openSubStructure;
        }
    }" class="py-1">
    <div class="flex items-center justify-between w-full">
        <div class="flex items-center justify-start space-x-2">
            @if($model->subs->isNotEmpty())
            {{-- arrow animasiyali deyisen--}}
            <button @click="toggle()" class="flex items-center justify-center appearance-none">
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
            wire:click.prevent="setStructure(@js(['id' => $model->id, 'list' => $listData, 'field' => $field, 'row' => $key, 'coded' => $isCoded ? 1 : 0]))"
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
        <ul class="flex flex-col pt-1 pl-2 bg-white rounded-lg shadow-sm"
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
                    $_level_name = __($structureLevels[$sub->level] ?? '');
                    $_select_value = ($field == 'structure_id' && $isCoded)
                                   ? $sub->code."{$suffixService->getNumberSuffix($sub->code)} {$_level_name}"
                                   : $sub->name;
                    $isCoded = $isCoded ?: 0;
                @endphp
                <x-radio-tree.item
                    :$isCoded
                    :$listData
                    :$field
                    :model="$sub"
                    :$key
                    :selected-id="$currentSelection"
                    :suffix-service="$suffixService"
                    :structure-levels="$structureLevels"
                    :selected-resolver="$resolveSelected"
                >
                    - {{ __($_select_value) }}
                </x-radio-tree.item>
            @endforeach
        </ul>
    @endif
</li>
