<div class="space-y-3">
    @if ($selectedOrder)
        <button type="button" wire:click="selectOrder('')"
            class="flex w-full items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-[12px] font-medium text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Hamısını göstər
        </button>
    @endif

    @foreach ($_order_categories as $category)
        @php $categoryTitle = $category->{'name_'.config('app.locale')}; @endphp
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
            <div class="px-3 py-2 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                {{ $categoryTitle }}
            </div>
            <div class="space-y-0.5 px-1.5 pb-1.5">
                @foreach ($category->orders as $order)
                    @php $isOn = $order->id == $selectedOrder; @endphp
                    <button type="button" wire:key="sidebar-order-{{ $order->id }}" wire:click="selectOrder('{{ $order->id }}')"
                        @class([
                            'group flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-[13px] font-medium transition',
                            'bg-zinc-900 text-white shadow-sm' => $isOn,
                            'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' => ! $isOn,
                        ])>
                        <span @class([
                            'h-1.5 w-1.5 shrink-0 rounded-full transition',
                            'bg-emerald-400' => $isOn,
                            'bg-zinc-300 group-hover:bg-zinc-400' => ! $isOn,
                        ])></span>
                        <span class="truncate">{{ $order->name }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
