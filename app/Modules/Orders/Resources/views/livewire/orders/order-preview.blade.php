@php
    $hasDocument = filled(data_get($order->template_snapshot, 'docx_path'));
    [$badgeColor, $badgeLabel] = \App\Modules\Orders\Livewire\AllOrders::statusBadge($order);
@endphp

<div x-data x-init="$wire.loadPdf()">
    <div class="sidemenu-title">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="hrm-num text-lg font-semibold text-ink" id="slide-over-title">{{ $order->order_no }}</h2>
            <x-status design="modern" :status-id="$badgeColor" :label="$badgeLabel" />
        </div>
        <p class="mt-1 text-sm text-ink-faint">
            {{ $order->order?->name ?? (data_get($order->template_snapshot, 'label') ?? '—') }}
            &middot; <span class="hrm-num">{{ \Carbon\Carbon::parse($order->given_date)->format('d.m.Y') }}</span>
            &middot; {{ $order->given_by }}
        </p>
    </div>

    <div class="py-6">
        @if (! $hasDocument)
            <p class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-10 text-center text-sm text-ink-faint">{{ __('orders::order_list.preview.no_document') }}</p>
        @elseif (! $pdfLoaded)
            <div class="flex items-center justify-center gap-2 rounded-xl border border-hairline bg-[#fafafa] py-10 text-sm text-ink-faint">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
                {{ __('orders::order_list.preview.loading') }}
            </div>
        @elseif ($pdf === '')
            <p class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-10 text-center text-sm text-ink-faint">{{ __('orders::order_list.preview.unavailable') }}</p>
        @else
            <div class="overflow-hidden rounded-xl border border-hairline">
                <iframe src="data:application/pdf;base64,{{ $pdf }}" class="h-[75vh] w-full" title="{{ $order->order_no }}"></iframe>
            </div>
        @endif
    </div>
</div>
