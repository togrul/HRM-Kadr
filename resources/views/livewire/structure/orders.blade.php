<div class="flex flex-col justify-start px-4 py-2 space-y-2">
    @foreach ($_order_categories as $category)
        <h1 class="font-medium text-neutral-600">{{ $category->{"name_".config('app.locale')} }}</h1>
        @foreach ($category->orders as $order)
            <button wire:key="{{ $order->id }}" wire:click="selectOrder('{{ $order->id }}')"
                @class([
                    'appearance-none bg-neutral-200/40 shadow-sm rounded-xl border border-neutral-100 py-2 px-4 hover:shadow-md transition-all duration-300',
                    'text-neutral-600' => $order->id != $selectedOrder,
                    'text-emerald-500' => $order->id == $selectedOrder,
                ])
            >
                {{ $order->name }}
            </button>
        @endforeach
    @endforeach
</div>
