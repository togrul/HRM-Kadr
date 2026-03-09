@props([
    'title' => __('orders::publish_readiness.titles.publish_readiness'),
    'ready' => false,
    'checks' => [],
    'blockedMessages' => [],
])

<div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h4 class="text-sm font-semibold text-slate-700">{{ $title }}</h4>
        @if($ready)
            <x-small-badge mode="green">{{ __('orders::publish_readiness.labels.ready_to_publish') }}</x-small-badge>
        @else
            <x-small-badge mode="red">{{ __('orders::publish_readiness.labels.blocked') }}</x-small-badge>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        @foreach($checks as $check)
            <div class="flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2">
                <span class="text-xs text-slate-700">{{ $check['label'] ?? '-' }}</span>
                @if(!empty($check['ok']))
                    <x-small-badge mode="green">{{ __('orders::publish_readiness.labels.ok') }}</x-small-badge>
                @else
                    <x-small-badge mode="red">{{ __('orders::publish_readiness.labels.missing') }}</x-small-badge>
                @endif
            </div>
        @endforeach
    </div>

    @if(!$ready && !empty($blockedMessages))
        <div class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2">
            <p class="text-xs font-semibold text-rose-700">{{ __('orders::publish_readiness.titles.publish_guard_messages') }}</p>
            <ul class="mt-1 list-disc pl-4 text-xs text-rose-700 space-y-1">
                @foreach($blockedMessages as $guardMessage)
                    <li>{{ $guardMessage }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
