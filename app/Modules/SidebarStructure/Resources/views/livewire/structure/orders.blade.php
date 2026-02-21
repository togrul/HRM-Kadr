<div class="flex flex-col justify-start space-y-2">
    @foreach ($_order_categories as $category)
        @php
          $categoryTitle = $category->{"name_".config('app.locale')};
        @endphp
        <x-surface-card :title="$categoryTitle">
          @foreach ($category->orders as $order)
          <button wire:key="{{ $order->id }}" wire:click="selectOrder('{{ $order->id }}')"
              @class([
                  'appearance-none w-full bg-neutral-200/40 shadow-sm rounded-xl border border-neutral-100 py-2 px-4 hover:shadow-md transition-all duration-300',
                  'text-neutral-600' => $order->id != $selectedOrder,
                  'text-emerald-500' => $order->id == $selectedOrder,
                  'mb-2' => !$loop->last
              ])
          >
              {{ $order->name }}
          </button>
          @endforeach
        </x-surface-card>
    @endforeach
</div>
