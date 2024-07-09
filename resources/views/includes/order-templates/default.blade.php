<div class="flex flex-col space-y-2">
    @if(!empty($selectedComponents[$i]))
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full sm:col-span-2 mt-3">
            @foreach($selectedComponents[$i] as $row => $_field)
                <x-dynamic-input
                    :list="$components"
                    :field="$service[$_field]['field']"
                    :title="$service[$_field]['title']"
                    :type="$_field"
                    :model="array_key_exists('model',$service[$_field]) ? ${$service[$_field]['model']} : null"
                    :key="$i"
                    :selectedName="array_key_exists('selectedName',$service[$_field]) ? $service[$_field]['selectedName'] : null"
                    :searchField="array_key_exists('searchField',$service[$_field]) ? $service[$_field]['searchField'] : null"
                    :isCoded="$coded_list[$i]"
                    :$row
                    :disabled="($i+1) <= count($originalComponents)"
                ></x-dynamic-input>
            @endforeach
        </div>
    @endif
</div>
