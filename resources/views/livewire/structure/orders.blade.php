<div class="flex flex-col space-y-2 px-4 py-2 justify-start">
    @foreach ($_order_categories as $category)
        <h1 class="font-medium">{{ $category->name }}</h1>
        @foreach ($category->orders as $order)
            <button wire:key="{{ $order->id }}" wire:click="selectOrder('{{ $order->shortname }}')" 
                @class([
                    'appearance-none bg-slate-50 rounded-xl py-3 px-4 transition-all duration-300',
                    'text-slate-600' => $order->shortname != $selectedOrder,
                    'text-emerald-500' => $order->shortname == $selectedOrder,
                ])
            >
                {{ $order->name }}
            </button>
        @endforeach
    @endforeach
</div>
