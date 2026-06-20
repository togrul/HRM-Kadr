<div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
    <x-filter.nav>
        @foreach ($this->getStatusFilters() as $filter)
            @php
                $hasPermission = !array_key_exists('permission', $filter) || auth()->user()->can($filter['permission']);
            @endphp

            @if ($hasPermission)
                <x-filter.item
                    wire:click.prevent="setStatus('{{ $filter['key'] }}')"
                    wire:loading.attr="disabled"
                    wire:target="setStatus"
                    :active="$status === $filter['key']"
                >
                    {{ $filter['label'] }}
                </x-filter.item>
            @endif
        @endforeach
    </x-filter.nav>
</div>
