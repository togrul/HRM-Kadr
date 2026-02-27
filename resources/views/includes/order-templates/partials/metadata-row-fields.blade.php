@php
    $currentTokens = collect($selectedComponents[$i] ?? [])->values();

    $fallbackGroup = [[
        'key' => 'main',
        'title' => null,
        'order' => 0,
        'grid_cols' => ['default' => 1, 'sm' => 2, 'md' => 3],
        'fields' => $currentTokens->all(),
    ]];

    $configuredGroups = collect($templateRowGroups ?? [])
        ->map(function ($group) use ($currentTokens) {
            $fields = collect($group['fields'] ?? [])
                ->filter(fn ($token) => $currentTokens->contains($token))
                ->values()
                ->all();

            return array_merge($group, ['fields' => $fields]);
        })
        ->filter(fn ($group) => ! empty($group['fields']))
        ->values();

    $groups = $configuredGroups->isNotEmpty() ? $configuredGroups : collect($fallbackGroup);
@endphp

@if($currentTokens->isNotEmpty())
    <div class="flex flex-col space-y-3 w-full sm:col-span-2 mt-3">
        @foreach($groups as $group)
            @php
                $gridCols = is_array($group['grid_cols'] ?? null) ? $group['grid_cols'] : ['default' => 1, 'sm' => 2, 'md' => 3];
                $gridClass = 'grid gap-2 ';
                $gridClass .= 'grid-cols-' . max(1, (int) ($gridCols['default'] ?? 1));
                foreach (['sm', 'md', 'lg', 'xl', '2xl'] as $breakpoint) {
                    if (isset($gridCols[$breakpoint]) && is_numeric($gridCols[$breakpoint])) {
                        $gridClass .= ' ' . $breakpoint . ':grid-cols-' . max(1, (int) $gridCols[$breakpoint]);
                    }
                }
            @endphp

            @if(! empty($group['title']))
                <h4 class="text-sm font-semibold text-slate-600">{{ $group['title'] }}</h4>
            @endif

            <div class="{{ $gridClass }}">
                @foreach($group['fields'] as $fieldIndex => $_field)
                    @php
                        $fieldConfig = $dynamicFieldCatalog[$_field] ?? [
                            'field' => ltrim((string) $_field, '$'),
                            'title' => \Illuminate\Support\Str::headline(ltrim((string) $_field, '$')),
                        ];

                        $fieldName = $fieldConfig['field'];
                        $resolvedLabel = $this->componentFieldLabel($i, $fieldName);
                        $resolvedValue = $this->componentFieldValue($i, $fieldName);

                        $colSpan = $fieldConfig['col_span'] ?? ['default' => 1];
                        if (is_numeric($colSpan)) {
                            $colSpan = ['default' => (int) $colSpan];
                        }

                        $spanClass = 'col-span-' . max(1, (int) ($colSpan['default'] ?? 1));
                        foreach (['sm', 'md', 'lg', 'xl', '2xl'] as $breakpoint) {
                            if (isset($colSpan[$breakpoint]) && is_numeric($colSpan[$breakpoint])) {
                                $spanClass .= ' ' . $breakpoint . ':col-span-' . max(1, (int) $colSpan[$breakpoint]);
                            }
                        }
                    @endphp

                    <div class="{{ $spanClass }}">
                        <x-dynamic-input
                            :list="$componentForms"
                            :field="$fieldName"
                            :title="$fieldConfig['title']"
                            :type="$_field"
                            :model="array_key_exists('model', $fieldConfig) ? ${$fieldConfig['model']} : null"
                            :key="$i"
                            :selectedName="array_key_exists('selectedName', $fieldConfig) ? $fieldConfig['selectedName'] : null"
                            :searchField="array_key_exists('searchField', $fieldConfig) ? $fieldConfig['searchField'] : null"
                            :input="array_key_exists('input', $fieldConfig) ? $fieldConfig['input'] : null"
                            :isCoded="($coded_list[$i] ?? false) || $fieldName === 'structure_id'"
                            :selectedLabel="$resolvedLabel"
                            :selectedValue="$resolvedValue"
                            :row="$fieldIndex"
                            :disabled="($i+1) <= count($originalComponents)"
                        />
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
